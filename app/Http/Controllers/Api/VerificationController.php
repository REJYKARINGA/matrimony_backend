<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\UserVerification;

class VerificationController extends Controller
{
    /**
     * Submit ID proof for verification
     */
    /**
     * Submit ID proof for verification
     */
    public function submitVerification(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'id_proof_type' => 'required|string|max:255',
            'id_proof_number' => 'nullable|string|max:255',
            'id_proof_front' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'id_proof_back' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            // Upload Front Side to Cloudinary
            $frontFile = $request->file('id_proof_front');
            $frontUpload = cloudinary()->uploadApi()->upload($frontFile->getRealPath(), [
                'folder' => 'matrimony/verifications/' . $user->id,
                'public_id' => 'id_front_' . $user->id . '_' . now()->timestamp,
                'transformation' => [
                    ['width' => 1200, 'height' => 1200, 'crop' => 'limit', 'quality' => 'auto:good'],
                ],
            ]);
            $frontUrl = $frontUpload['secure_url'];

            // Upload Back Side if provided
            $backUrl = null;
            if ($request->hasFile('id_proof_back')) {
                $backFile = $request->file('id_proof_back');
                $backUpload = cloudinary()->uploadApi()->upload($backFile->getRealPath(), [
                    'folder' => 'matrimony/verifications/' . $user->id,
                    'public_id' => 'id_back_' . $user->id . '_' . now()->timestamp,
                    'transformation' => [
                        ['width' => 1200, 'height' => 1200, 'crop' => 'limit', 'quality' => 'auto:good'],
                    ],
                ]);
                $backUrl = $backUpload['secure_url'];
            }

            $verification = UserVerification::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'id_proof_type' => $request->id_proof_type,
                    'id_proof_number' => $request->id_proof_number,
                    'id_proof_front_url' => $frontUrl,
                    'id_proof_back_url' => $backUrl,
                    'status' => 'pending',
                    'rejection_reason' => null,
                ]
            );

            return response()->json([
                'message' => 'ID proof submitted successfully',
                'verification' => $verification
            ]);
        } catch (\Exception $e) {
            Log::error("Verification Submission Error: " . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred during submission',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get verification status
     */
    public function getStatus(Request $request)
    {
        $user = $request->user();
        $verification = $user->verification;

        return response()->json([
            'verification' => $verification
        ]);
    }
}
