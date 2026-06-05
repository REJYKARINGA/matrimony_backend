<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactUnlockRequest;
use App\Models\AdminSetting;
use App\Models\Notification;
use App\Models\User;

class ContactUnlockRequestController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'target_user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($request) {
                    if ((int) $value === (int) $request->user()->id) {
                        $fail('You cannot send a permission request to yourself.');
                    }
                },
            ],
        ]);

        $user = $request->user();

        $setting = AdminSetting::first();
        $featureEnabled = $setting && $setting->user_contact_permission_unlock;

        if (!$featureEnabled) {
            return response()->json([
                'error' => 'Permission unlock feature is currently disabled by admin',
            ], 403);
        }

        $existing = ContactUnlockRequest::where('requester_id', $user->id)
            ->where('target_user_id', $request->target_user_id)
            ->first();

        if ($existing) {
            $statusMsg = $existing->status === 'pending'
                ? 'You have already sent a permission request to this user'
                : ($existing->status === 'approved'
                    ? 'Permission already granted for this user'
                    : 'Your previous request was rejected');

            return response()->json([
                'error' => $statusMsg,
                'status' => $existing->status,
                'request' => $existing,
            ], 400);
        }

        $contactUnlockRequest = ContactUnlockRequest::create([
            'requester_id' => $user->id,
            'target_user_id' => $request->target_user_id,
            'status' => 'pending',
        ]);

        $targetUser = User::find($request->target_user_id);

        try {
            Notification::create([
                'user_id' => $targetUser->id,
                'sender_id' => $user->id,
                'type' => 'contact_unlock_request',
                'title' => 'Contact Unlock Request',
                'message' => 'requested permission to unlock your contact details',
                'reference_id' => $contactUnlockRequest->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send permission request notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission request sent successfully',
            'request' => $contactUnlockRequest,
        ]);
    }

    public function incoming(Request $request)
    {
        $user = $request->user();

        $requests = ContactUnlockRequest::with([
            'requester:id,matrimony_id,phone',
            'requester.userProfile:user_id,first_name,last_name,profile_picture',
        ])
            ->where('target_user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['requests' => $requests]);
    }

    public function sent(Request $request)
    {
        $user = $request->user();

        $requests = ContactUnlockRequest::with([
            'targetUser:id,matrimony_id,phone',
            'targetUser.userProfile:user_id,first_name,last_name,profile_picture',
        ])
            ->where('requester_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['requests' => $requests]);
    }

    public function check(Request $request, $userId)
    {
        $user = $request->user();

        $contactUnlockRequest = ContactUnlockRequest::where('requester_id', $user->id)
            ->where('target_user_id', $userId)
            ->first();

        if (!$contactUnlockRequest) {
            return response()->json([
                'status' => 'none',
                'request' => null,
            ]);
        }

        return response()->json([
            'status' => $contactUnlockRequest->status,
            'request' => $contactUnlockRequest,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = $request->user();

        $contactUnlockRequest = ContactUnlockRequest::where('id', $id)
            ->where('target_user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $contactUnlockRequest->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);

        $requester = User::find($contactUnlockRequest->requester_id);

        try {
            Notification::create([
                'user_id' => $requester->id,
                'sender_id' => $user->id,
                'type' => 'contact_unlock_request_approved',
                'title' => 'Permission Approved',
                'message' => 'approved your request to unlock their contact. You can now unlock using your wallet.',
                'reference_id' => $contactUnlockRequest->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send approval notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission request approved',
            'request' => $contactUnlockRequest,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $user = $request->user();

        $contactUnlockRequest = ContactUnlockRequest::where('id', $id)
            ->where('target_user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $contactUnlockRequest->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        $requester = User::find($contactUnlockRequest->requester_id);

        try {
            Notification::create([
                'user_id' => $requester->id,
                'sender_id' => $user->id,
                'type' => 'contact_unlock_request_rejected',
                'title' => 'Permission Rejected',
                'message' => 'declined your request to unlock their contact',
                'reference_id' => $contactUnlockRequest->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send rejection notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission request rejected',
            'request' => $contactUnlockRequest,
        ]);
    }

    public function pendingCount(Request $request)
    {
        $user = $request->user();

        $count = ContactUnlockRequest::where('target_user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return response()->json(['pending_count' => $count]);
    }
}
