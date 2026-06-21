<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerOffice;
use App\Models\PartnerAgent;
use App\Models\Reference;
use App\Models\Payment;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerDashboardController extends Controller
{
    private function getOfficeForUser(Request $request)
    {
        $user = $request->user();
        $agent = PartnerAgent::where('user_id', $user->id)->with('office')->first();

        if (!$agent || !$agent->office) {
            abort(403, 'No partner office assigned to this user.');
        }

        if ($agent->office->status !== 'active') {
            abort(403, 'Your partner office account is inactive.');
        }

        return $agent->office;
    }

    public function getStats(Request $request)
    {
        $office = $this->getOfficeForUser($request);

        $totalRegistrations = Reference::where('partner_office_id', $office->id)->count();
        $referredUserIds = Reference::where('partner_office_id', $office->id)->pluck('referenced_user_id');
        $totalRevenue = Payment::whereIn('user_id', $referredUserIds)
            ->where('status', 'completed')
            ->sum('amount');

        $monthlyRegistrations = Reference::where('partner_office_id', $office->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $monthlyRevenue = Payment::whereIn('user_id', $referredUserIds)
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $commissionReg = $totalRegistrations * $office->commission_per_registration;
        $revenueShare = $totalRevenue * ($office->revenue_share_percent / 100);
        $totalEarned = $commissionReg + $revenueShare;

        $totalPaid = $office->payouts()->where('status', 'paid')->sum('amount');
        $pendingPayout = max(0, $totalEarned - $totalPaid);

        return response()->json([
            'stats' => [
                'total_registrations' => $totalRegistrations,
                'monthly_registrations' => $monthlyRegistrations,
                'total_revenue_generated' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'commission_per_registration' => $office->commission_per_registration,
                'revenue_share_percent' => $office->revenue_share_percent,
                'commission_from_registrations' => $commissionReg,
                'revenue_share_amount' => $revenueShare,
                'total_earned' => $totalEarned,
                'total_paid' => $totalPaid,
                'pending_payout' => $pendingPayout,
            ],
            'office' => $office,
        ]);
    }

    public function getRegistrations(Request $request)
    {
        $office = $this->getOfficeForUser($request);

        $query = Reference::where('partner_office_id', $office->id)
            ->with(['referredUser.userProfile', 'referredBy']);

        if ($request->agent_id) {
            $query->where('partner_agent_id', $request->agent_id);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $registrations = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json(['registrations' => $registrations]);
    }

    public function getAgents(Request $request)
    {
        $office = $this->getOfficeForUser($request);

        $agents = $office->agents()->withCount(['user.givenReferences' => function ($q) use ($office) {
            $q->where('partner_office_id', $office->id);
        }])->get();

        return response()->json(['agents' => $agents]);
    }

    public function addAgent(Request $request)
    {
        $office = $this->getOfficeForUser($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'create_login' => 'boolean',
            'password' => 'required_if:create_login,true|nullable|string|min:6',
        ]);

        $validated['partner_office_id'] = $office->id;
        $validated['status'] = 'active';

        if ($request->create_login) {
            $user = \App\Models\User::create([
                'name' => $validated['name'],
                'email' => $validated['email'] ?? ('agent_' . uniqid() . '@partner.local'),
                'phone' => $validated['phone'] ?? null,
                'password' => bcrypt($validated['password']),
                'role' => 'partner_office',
                'status' => 'active',
            ]);
            $validated['user_id'] = $user->id;
        }

        $agent = PartnerAgent::create($validated);

        return response()->json([
            'message' => 'Agent added successfully',
            'agent' => $agent->load('user'),
        ], 201);
    }

    public function getBankAccounts(Request $request)
    {
        $user = $request->user();
        $accounts = BankAccount::where('user_id', $user->id)->get();

        return response()->json(['bank_accounts' => $accounts]);
    }

    public function addBankAccount(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'ifsc_code' => 'required|string|max:20',
            'is_primary' => 'boolean',
        ]);

        $validated['user_id'] = $user->id;

        if ($validated['is_primary'] ?? count(BankAccount::where('user_id', $user->id)->get()) === 0) {
            BankAccount::where('user_id', $user->id)->update(['is_primary' => false]);
            $validated['is_primary'] = true;
        }

        $account = BankAccount::create($validated);

        return response()->json([
            'message' => 'Bank account added successfully',
            'bank_account' => $account,
        ], 201);
    }

    public function setPrimaryBankAccount(Request $request, $id)
    {
        $user = $request->user();
        $account = BankAccount::where('user_id', $user->id)->findOrFail($id);

        BankAccount::where('user_id', $user->id)->update(['is_primary' => false]);
        $account->update(['is_primary' => true]);

        return response()->json(['message' => 'Primary bank account updated']);
    }

    public function deleteBankAccount(Request $request, $id)
    {
        $user = $request->user();
        $account = BankAccount::where('user_id', $user->id)->findOrFail($id);
        $account->delete();

        return response()->json(['message' => 'Bank account deleted']);
    }

    public function requestPayout(Request $request)
    {
        $office = $this->getOfficeForUser($request);
        $user = $request->user();

        $totalRegistrations = Reference::where('partner_office_id', $office->id)->count();
        $referredUserIds = Reference::where('partner_office_id', $office->id)->pluck('referenced_user_id');
        $totalRevenue = Payment::whereIn('user_id', $referredUserIds)
            ->where('status', 'completed')
            ->sum('amount');

        $commissionReg = $totalRegistrations * $office->commission_per_registration;
        $revenueShare = $totalRevenue * ($office->revenue_share_percent / 100);
        $totalEarned = $commissionReg + $revenueShare;
        $totalPaid = $office->payouts()->where('status', 'paid')->sum('amount');
        $pendingPayout = max(0, $totalEarned - $totalPaid);

        if ($pendingPayout <= 0) {
            return response()->json(['error' => 'No pending payout balance.'], 400);
        }

        $primaryAccount = BankAccount::where('user_id', $user->id)->where('is_primary', true)->first();
        if (!$primaryAccount) {
            return response()->json(['error' => 'Please add and set a primary bank account first.'], 400);
        }

        $payout = $office->payouts()->create([
            'amount' => $pendingPayout,
            'status' => 'pending',
            'notes' => "Payout requested by {$user->name}",
        ]);

        return response()->json([
            'message' => "Payout request of ₹{$pendingPayout} submitted successfully.",
            'payout' => $payout,
        ]);
    }
}
