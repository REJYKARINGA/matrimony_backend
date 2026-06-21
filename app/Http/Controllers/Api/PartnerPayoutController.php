<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerPayout;
use App\Models\PartnerOffice;
use Illuminate\Http\Request;

class PartnerPayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerPayout::with(['office', 'processedBy']);

        if ($request->partner_office_id) {
            $query->where('partner_office_id', $request->partner_office_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json(['payouts' => $payouts]);
    }

    public function processPayout(Request $request, $id)
    {
        $payout = PartnerPayout::with('office')->findOrFail($id);

        if ($payout->status !== 'pending') {
            return response()->json(['error' => 'Payout is not in pending status.'], 400);
        }

        $payout->update([
            'status' => 'paid',
            'transfer_id' => $request->transfer_id,
            'notes' => $request->notes,
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payout marked as paid successfully.',
            'payout' => $payout->fresh()->load(['office', 'processedBy']),
        ]);
    }

    public function rejectPayout(Request $request, $id)
    {
        $payout = PartnerPayout::findOrFail($id);

        if ($payout->status !== 'pending') {
            return response()->json(['error' => 'Payout is not in pending status.'], 400);
        }

        $payout->update([
            'status' => 'rejected',
            'notes' => $request->notes ?? 'Rejected by admin',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payout rejected.',
            'payout' => $payout->fresh()->load(['office', 'processedBy']),
        ]);
    }

    public function getPendingTotal()
    {
        $total = PartnerPayout::where('status', 'pending')->sum('amount');
        $count = PartnerPayout::where('status', 'pending')->count();

        return response()->json([
            'total_pending' => $total,
            'pending_count' => $count,
        ]);
    }
}
