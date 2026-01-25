<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Message;

class MessageController extends Controller
{
    /**
     * Get all messages for the authenticated user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Subquery to get the latest message ID for each conversation
        $latestMessageIds = Message::selectRaw('MAX(id) as id')
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->groupByRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END', [$user->id])
            ->pluck('id');

        // Fetch valid conversation messages using the IDs from the subquery
        $conversations = Message::whereIn('id', $latestMessageIds)
            ->with([
                'sender:id,email',
                'sender.userProfile:user_id,first_name,last_name,profile_picture',
                'receiver:id,email',
                'receiver.userProfile:user_id,first_name,last_name,profile_picture'
            ])
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return response()->json([
            'conversations' => $conversations
        ]);
    }

    /**
     * Store a new message
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $sender = $request->user();
        $receiver = User::find($request->receiver_id);

        if (!$receiver) {
            return response()->json([
                'error' => 'Receiver not found'
            ], 404);
        }

        // Check if users are matched before allowing messaging
        $isMatched = \App\Models\UserMatch::where(function ($query) use ($sender, $receiver) {
            $query->where('user1_id', $sender->id)
                ->where('user2_id', $receiver->id);
        })->orWhere(function ($query) use ($sender, $receiver) {
            $query->where('user1_id', $receiver->id)
                ->where('user2_id', $sender->id);
        })->exists();

        if (!$isMatched) {
            return response()->json([
                'error' => 'You can only message matched users'
            ], 403);
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'message' => $request->message
        ]);

        // Create notification for the receiver
        \App\Models\Notification::create([
            'user_id' => $receiver->id,
            'sender_id' => $sender->id,
            'type' => 'message',
            'title' => 'New Message',
            'message' => "You have received a new message from {$sender->userProfile->first_name}",
            'reference_id' => $message->id
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => $message->load(['sender', 'receiver'])
        ]);
    }

    /**
     * Get messages with a specific user
     */
    public function getMessagesWithUser($userId, Request $request)
    {
        $currentUser = $request->user();
        $otherUser = User::find($userId);

        if (!$otherUser) {
            return response()->json([
                'error' => 'User not found'
            ], 404);
        }

        // Mark unread messages from this user as read
        Message::where('sender_id', $otherUser->id)
            ->where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        $messages = Message::where(function ($query) use ($currentUser, $otherUser) {
            $query->where(function ($q) use ($currentUser, $otherUser) {
                $q->where('sender_id', $currentUser->id)
                    ->where('receiver_id', $otherUser->id);
            })->orWhere(function ($q) use ($currentUser, $otherUser) {
                $q->where('sender_id', $otherUser->id)
                    ->where('receiver_id', $currentUser->id);
            });
        })
            ->with([
                'sender:id,email',
                'sender.userProfile:first_name,last_name,profile_picture',
                'receiver:id,email',
                'receiver.userProfile:first_name,last_name,profile_picture'
            ])
            ->orderBy('sent_at', 'asc')
            ->get();

        return response()->json([
            'messages' => $messages
        ]);
    }

    /**
     * Mark a message as read
     */
    public function markAsRead($id, Request $request)
    {
        $message = Message::find($id);

        if (!$message) {
            return response()->json([
                'error' => 'Message not found'
            ], 404);
        }

        // Verify that the authenticated user is the receiver
        if ($message->receiver_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        $message->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json([
            'message' => 'Message marked as read'
        ]);
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(Request $request)
    {
        $user = $request->user();

        $unreadCount = Message::where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $unreadCount
        ]);
    }
}
