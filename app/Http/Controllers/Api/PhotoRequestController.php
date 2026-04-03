<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhotoRequest;
use App\Models\User;
use App\Models\Notification;

class PhotoRequestController extends Controller
{
    public function sendRequest(Request $request, $receiverId)
    {
        $user = $request->user();

        if ($user->id == $receiverId) {
            return response()->json(['error' => 'You cannot send a photo request to yourself'], 400);
        }

        $receiver = User::find($receiverId);
        if (!$receiver) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if request already exists
        $existing = PhotoRequest::where('requester_id', $user->id)
            ->where('receiver_id', $receiverId)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'Request already sent', 'status' => $existing->status], 400);
        }

        $photoRequest = PhotoRequest::create([
            'requester_id' => $user->id,
            'receiver_id'  => $receiverId,
            'status'       => 'pending',
        ]);

        // Notify the receiver about the new photo request
        $senderName = trim(($user->userProfile->first_name ?? '') . ' ' . ($user->userProfile->last_name ?? ''));
        Notification::create([
            'user_id'      => $receiverId,
            'sender_id'    => $user->id,
            'type'         => 'photo_request',
            'title'        => 'New Photo Request',
            'message'      => ($senderName ?: 'Someone') . ' has requested access to your photos.',
            'reference_id' => $photoRequest->id,
        ]);

        return response()->json([
            'message' => 'Photo request sent successfully',
            'data'    => $photoRequest,
        ], 201);
    }

    public function acceptRequest(Request $request, $id)
    {
        $user = $request->user();

        $photoRequest = PhotoRequest::find($id);
        if (!$photoRequest || $photoRequest->receiver_id != $user->id) {
            return response()->json(['error' => 'Photo request not found or unauthorized'], 404);
        }

        $photoRequest->status = 'accepted';
        $photoRequest->save();

        // Notify the requester that their request was accepted
        $acceptorName = trim(($user->userProfile->first_name ?? '') . ' ' . ($user->userProfile->last_name ?? ''));
        Notification::create([
            'user_id'      => $photoRequest->requester_id,
            'sender_id'    => $user->id,
            'type'         => 'photo_request_accepted',
            'title'        => 'Photo Request Accepted!',
            'message'      => ($acceptorName ?: 'A user') . ' accepted your photo request. You can now view their photos.',
            'reference_id' => $photoRequest->id,
        ]);

        return response()->json([
            'message' => 'Photo request accepted',
            'data'    => $photoRequest,
        ]);
    }

    public function rejectRequest(Request $request, $id)
    {
        $user = $request->user();

        $photoRequest = PhotoRequest::find($id);
        if (!$photoRequest || $photoRequest->receiver_id != $user->id) {
            return response()->json(['error' => 'Photo request not found or unauthorized'], 404);
        }

        $photoRequest->status = 'rejected';
        $photoRequest->save();

        return response()->json([
            'message' => 'Photo request rejected',
            'data'    => $photoRequest,
        ]);
    }

    public function getPendingRequests(Request $request)
    {
        $user = $request->user();

        // Return ALL incoming requests with status, newest first
        $requests = PhotoRequest::with(['requester.userProfile', 'requester.profilePhotos'])
            ->where('receiver_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'requests' => $requests,
        ]);
    }
}
