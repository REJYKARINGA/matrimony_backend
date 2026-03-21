<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BlockedUser;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::where('status', 'active')
            ->whereNull('deleted_at') // Exclude soft deleted users
            ->with([
                'userProfile.religionModel',
                'userProfile.casteModel',
                'userProfile.subCasteModel',
                'userProfile.educationModel',
                'userProfile.occupationModel',
                'profilePhotos' => function ($q) {
                    $q->where('is_primary', true)->limit(1);
                }
            ])
            ->paginate(15);

        return response()->json([
            'users' => UserResource::collection($users)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // This is handled by AuthController@register
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::with([
            'userProfile.religionModel',
            'userProfile.casteModel',
            'userProfile.subCasteModel',
            'userProfile.educationModel',
            'userProfile.occupationModel',
            'familyDetails',
            'preferences',
            'profilePhotos' => function ($q) {
                $q->where('is_primary', true)->limit(1);
            }
        ])
            ->whereNull('deleted_at') // Exclude soft deleted users
            ->find($id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        return response()->json([
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        $user->update($request->only(['email', 'phone', 'role', 'status']));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        $user->update(['status' => 'deleted']);
        // In a real app, you might want to soft delete or actually delete

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Block a user
     */
    public function blockUser(Request $request, $userId)
    {
        $currentUser = $request->user();
        $targetUser = User::find($userId);

        if (!$targetUser) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        if ($currentUser->id == $targetUser->id) {
            return response()->json([
                'error' => 'Cannot block yourself'
            ], 400);
        }

        // Check if already blocked
        $existingBlock = BlockedUser::where('user_id', $currentUser->id)
            ->where('blocked_user_id', $targetUser->id)
            ->first();

        if ($existingBlock) {
            return response()->json([
                'error' => 'User already blocked'
            ], 409);
        }

        $block = BlockedUser::create([
            'user_id' => $currentUser->id,
            'blocked_user_id' => $targetUser->id,
            'reason' => $request->reason ?? null
        ]);

        return response()->json([
            'message' => 'User blocked successfully',
            'block' => $block
        ]);
    }

    /**
     * Unblock a user
     */
    public function unblockUser(Request $request, $userId)
    {
        $currentUser = $request->user();

        $block = BlockedUser::where('user_id', $currentUser->id)
            ->where('blocked_user_id', $userId)
            ->first();

        if (!$block) {
            return response()->json([
                'error' => 'User not blocked'
            ], 404);
        }

        $block->delete();

        return response()->json([
            'message' => 'User unblocked successfully'
        ]);
    }

    /**
     * Get blocked users
     */
    public function getBlockedUsers(Request $request)
    {
        $currentUser = $request->user();

        $blockedUsers = BlockedUser::where('user_id', $currentUser->id)
            ->with([
                'blockedUser',
                'blockedUser.userProfile.casteModel',
                'blockedUser.userProfile.educationModel',
                'blockedUser.userProfile.occupationModel',
                'blockedUser.profilePhotos' => function ($q) {
                    $q->where('is_primary', true)->limit(1);
                },
            ])
            ->paginate(10);

        // Transform each block to include rich user card data
        $transformed = $blockedUsers->getCollection()->map(function ($block) use ($request) {
            return [
                'id'              => $block->id,
                'user_id'         => $block->user_id,
                'blocked_user_id' => $block->blocked_user_id,
                'reason'          => $block->reason,
                'blocked_at'      => $block->blocked_at,
                'blocked_user'    => $block->blockedUser
                    ? (new \App\Http\Resources\UserCardResource($block->blockedUser))->toArray($request)
                    : null,
            ];
        });

        return response()->json([
            'blocked_users' => [
                'current_page'   => $blockedUsers->currentPage(),
                'data'           => $transformed,
                'last_page'      => $blockedUsers->lastPage(),
                'per_page'       => $blockedUsers->perPage(),
                'total'          => $blockedUsers->total(),
                'next_page_url'  => $blockedUsers->nextPageUrl(),
                'prev_page_url'  => $blockedUsers->previousPageUrl(),
            ],
        ]);
    }

    /**
     * Report a user
     */
    public function reportUser(Request $request, $userId)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $reporter = $request->user();
        
        try {
            $report = \App\Models\UserReport::create([
                'reporter_id' => $reporter->id,
                'reported_id' => $userId,
                'reason' => $request->reason,
                'status' => 'pending'
            ]);

            // Penalty check: 10 reports = deactivation
            $count = \App\Models\UserReport::where('reported_id', $userId)->count();
            if ($count >= 10) {
                $user = \App\Models\User::find($userId);
                if ($user && $user->status !== 'deactivated') {
                    $user->update(['status' => 'deactivated']);
                }
            }

            return response()->json([
                'message' => 'User reported successfully' . ($count >= 10 ? ' and account deactivated' : ''),
                'report' => $report,
                'reports_count' => $count
            ], 201);
        } catch (\Exception $e) {
            Log::error("Failed to report user {$userId}: " . $e->getMessage());
            return response()->json([
                'error' => 'Reporting is temporarily unavailable',
                'details' => $e->getMessage()
            ], 503);
        }
    }
}
