<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BlockedUser;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::where('status', 'active')
            ->whereNull('deleted_at') // Exclude soft deleted users
            ->with(['userProfile', 'profilePhotos'])
            ->paginate(15);

        return response()->json([
            'users' => $users
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
        $user = User::with(['userProfile', 'familyDetails', 'preferences', 'profilePhotos'])
            ->whereNull('deleted_at') // Exclude soft deleted users
            ->find($id);

        if (!$user) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        return response()->json([
            'user' => $user
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
                'blockedUser:id,email',
                'blockedUser.userProfile:first_name,last_name,profile_picture'
            ])
            ->paginate(10);

        return response()->json([
            'blocked_users' => $blockedUsers
        ]);
    }
}
