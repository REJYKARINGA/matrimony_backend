<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerOffice;
use App\Models\Reference;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerOfficeController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerOffice::withCount(['agents', 'referredUsers']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('office_code', 'like', "%{$request->search}%")
                    ->orWhere('city', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $offices = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'offices' => $offices,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'commission_per_registration' => 'nullable|numeric|min:0',
            'revenue_share_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,inactive',
            'logo' => 'nullable|string',
        ]);

        $validated['office_code'] = PartnerOffice::generateOfficeCode();
        $validated['created_by'] = $request->user()->id;

        $office = PartnerOffice::create($validated);

        return response()->json([
            'message' => 'Partner office created successfully',
            'office' => $office,
        ], 201);
    }

    public function show($id)
    {
        $office = PartnerOffice::with(['agents', 'createdBy'])
            ->withCount(['referredUsers', 'agents'])
            ->findOrFail($id);

        $stats = $this->getOfficeStats($office);

        return response()->json([
            'office' => $office,
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, $id)
    {
        $office = PartnerOffice::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'commission_per_registration' => 'nullable|numeric|min:0',
            'revenue_share_percent' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|in:active,inactive',
            'logo' => 'nullable|string',
        ]);

        $office->update($validated);

        return response()->json([
            'message' => 'Partner office updated successfully',
            'office' => $office,
        ]);
    }

    public function destroy($id)
    {
        $office = PartnerOffice::findOrFail($id);
        $office->delete();

        return response()->json([
            'message' => 'Partner office deleted successfully',
        ]);
    }

    public function getStats($id)
    {
        $office = PartnerOffice::findOrFail($id);
        $stats = $this->getOfficeStats($office);

        return response()->json(['stats' => $stats]);
    }

    public function getRegistrations(Request $request)
    {
        $query = \App\Models\Reference::with([
            'referredUser:id,matrimony_id,email,phone,role,created_at',
            'referredUser.userProfile:id,user_id,first_name,last_name',
            'referredBy:id,name,matrimony_id',
            'partnerAgent',
            'partnerOffice',
        ])->whereNotNull('partner_office_id');

        if ($request->partner_office_id) {
            $query->where('partner_office_id', $request->partner_office_id);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $registrations = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 20);

        return response()->json(['registrations' => $registrations]);
    }

    private function getOfficeStats(PartnerOffice $office)
    {
        $totalRegistrations = Reference::where('partner_office_id', $office->id)->count();

        $referredUserIds = Reference::where('partner_office_id', $office->id)
            ->pluck('referenced_user_id');

        $totalRevenue = Payment::whereIn('user_id', $referredUserIds)
            ->where('status', 'completed')
            ->sum('amount');

        $commissionFromRegistrations = $totalRegistrations * $office->commission_per_registration;
        $revenueShareAmount = $totalRevenue * ($office->revenue_share_percent / 100);

        $totalPaid = $office->payouts()
            ->where('status', 'paid')
            ->sum('amount');

        $pendingPayout = ($commissionFromRegistrations + $revenueShareAmount) - $totalPaid;

        return [
            'total_registrations' => $totalRegistrations,
            'total_revenue_generated' => $totalRevenue,
            'commission_from_registrations' => $commissionFromRegistrations,
            'revenue_share_amount' => $revenueShareAmount,
            'total_earned' => $commissionFromRegistrations + $revenueShareAmount,
            'total_paid' => $totalPaid,
            'pending_payout' => max(0, $pendingPayout),
        ];
    }
}
