<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reference;
use App\Models\User;

class ReferenceController extends Controller
{
    /**
     * GET /api/references/my-code
     * Returns the authenticated user's own reference code.
     * Every user (especially mediators) can share this with new users.
     */
    public function myCode(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'reference_code' => $user->reference_code,
            'matrimony_id' => $user->matrimony_id,
            'role' => $user->role,
        ]);
    }

    /**
     * GET /api/references/my-referrals
     * Returns the list of users who registered using the authenticated user's reference code,
     * along with each user's purchased_count (number of contact unlocks they made).
     */
    public function myReferrals(Request $request)
    {
        $user = $request->user();

        $referrals = Reference::where('referenced_by_id', $user->id)
            ->with([
                'referredUser:id,matrimony_id,email,phone,reference_code,role,created_at',
                'referredUser.userProfile',
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($ref) {
                return [
                    'id' => $ref->id,
                    'reference_code' => $ref->reference_code,
                    'reference_type' => $ref->reference_type,
                    'purchased_count' => $ref->purchased_count,
                    'total_paid_amount' => $ref->total_paid_amount,
                    'joined_at' => $ref->created_at,
                    'referred_user' => $ref->referredUser,
                ];
            });

        return response()->json([
            'total_referrals' => $referrals->count(),
            'total_purchases' => $referrals->sum('purchased_count'),
            'total_paid' => $referrals->sum('total_paid_amount'),
            'referrals' => $referrals,
        ]);
    }

    /**
     * GET /api/references/my-referrer
     * Returns the reference record for the authenticated user —
     * i.e., who referred them, and the current purchase count for their account.
     */
    public function myReferrer(Request $request)
    {
        $user = $request->user();

        $reference = Reference::where('referenced_user_id', $user->id)
            ->with('referredBy:id,matrimony_id,email,role')
            ->first();

        if (!$reference) {
            return response()->json([
                'message' => 'No reference found for this user.',
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $reference->id,
                'reference_code' => $reference->reference_code,
                'reference_type' => $reference->reference_type,
                'purchased_count' => $reference->purchased_count,
                'referred_by' => $reference->referredBy,
                'created_at' => $reference->created_at,
            ],
        ]);
    }

    /**
     * GET /api/references
     * Admin: list ALL reference records with pagination.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $references = Reference::with([
            'referredBy:id,matrimony_id,email,role',
            'referredUser:id,matrimony_id,email,role',
        ])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($references);
    }

    /**
     * GET /api/references/{id}
     * Admin: view a single reference record.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!in_array($user->role, ['admin', 'staff'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reference = Reference::with([
            'referredBy:id,matrimony_id,email,role',
            'referredUser:id,matrimony_id,email,role',
        ])->findOrFail($id);

        return response()->json(['data' => $reference]);
    }

    /**
     * GET /api/references/validate/{code}
     * Public-ish: check if a reference code is valid before registration.
     * Returns basic info about the referrer so the UI can confirm.
     */
    public function validateCode(Request $request, $code)
    {
        $referrer = User::where('reference_code', strtoupper($code))->first();

        if (!$referrer) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid reference code.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'referrer_id' => $referrer->matrimony_id,
            'referrer_role' => $referrer->role,
        ]);
    }

    /**
     * POST /api/references/add
     * Mediator: manually link an existing user to their referral list.
     * Accepts either matrimony_id or email of the user to add.
     */
    public function addReferral(Request $request)
    {
        $mediator = $request->user();

        $request->validate([
            'identifier' => 'required|string|size:6', // target user's reference_code
        ]);

        $code = strtoupper(trim($request->identifier));

        // Find the target user by THEIR reference_code
        $targetUser = User::where('reference_code', $code)->first();

        if (!$targetUser) {
            return response()->json([
                'error' => 'User not found with code ' . $code . '.',
            ], 404);
        }

        // Cannot add yourself
        if ($targetUser->id === $mediator->id) {
            return response()->json([
                'error' => 'You cannot add yourself as a referral.',
            ], 422);
        }

        // Check if this user already has a reference record (referred by anyone)
        $existing = Reference::where('referenced_user_id', $targetUser->id)->first();

        if ($existing) {
            return response()->json([
                'error' => 'This user is already linked to a referral (by ' .
                    ($existing->referenced_by_id === $mediator->id ? 'you' : 'another mediator') . ').',
            ], 422);
        }

        // Create the reference record
        $reference = Reference::create([
            'referenced_by_id' => $mediator->id,
            'referenced_user_id' => $targetUser->id,
            'reference_code' => $mediator->reference_code,
            'reference_type' => $mediator->role,
            'purchased_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User successfully added to your referral list.',
            'data' => [
                'id' => $reference->id,
                'reference_code' => $reference->reference_code,
                'purchased_count' => $reference->purchased_count,
                'referred_user' => [
                    'id' => $targetUser->id,
                    'matrimony_id' => $targetUser->matrimony_id,
                    'email' => $targetUser->email,
                    'role' => $targetUser->role,
                    'created_at' => $targetUser->created_at,
                ],
                'joined_at' => $reference->created_at,
            ],
        ], 201);
    }
}
