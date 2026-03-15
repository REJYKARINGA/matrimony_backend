<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PhotoRequest;
use App\Models\User;

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
            'receiver_id' => $receiverId,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Photo request sent successfully',
            'data' => $photoRequest
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

        return response()->json([
            'message' => 'Photo request accepted',
            'data' => $photoRequest
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
            'data' => $photoRequest
        ]);
    }

    public function getPendingRequests(Request $request)
    {
        $user = $request->user();
        
        $requests = PhotoRequest::with(['requester.userProfile', 'requester.profilePhotos'])
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'requests' => $requests
        ]);
    }
}
