<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationSettingController extends Controller
{
    /**
     * Get authenticated user's notification settings
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();
        
        $settings = NotificationSetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'notify_matches' => true,
                'notify_messages' => true,
                'notify_profile_views' => true,
                'notify_interests' => true,
                'notify_email' => true,
                'notify_push' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update authenticated user's notification settings
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'notify_matches' => 'sometimes|boolean',
            'notify_messages' => 'sometimes|boolean',
            'notify_profile_views' => 'sometimes|boolean',
            'notify_interests' => 'sometimes|boolean',
            'notify_email' => 'sometimes|boolean',
            'notify_push' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $settings = NotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'notify_matches',
                'notify_messages',
                'notify_profile_views',
                'notify_interests',
                'notify_email',
                'notify_push',
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully',
            'data' => $settings
        ]);
    }
}
