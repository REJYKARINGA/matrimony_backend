<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\UserVerification;

class VerificationController extends Controller
{
    /**
     * Submit ID proof for verification
     */
    public function submitVerification(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'id_proof_type' => 'required|string|max:255',
            'id_proof_number' => 'nullable|string|max:255',
            'id_proof_front' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'id_proof_back' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        // Generate professional filenames
        $timestamp = time();
        $userId = $user->id;

        $frontFile = $request->file('id_proof_front');
        $frontExt = $frontFile->getClientOriginalExtension();
        $frontFilename = "{$userId}_verification_front_{$timestamp}.{$frontExt}";
        $frontPath = $frontFile->storeAs('id_proofs', $frontFilename, 'public');

        $backPath = null;
        if ($request->hasFile('id_proof_back')) {
            $backFile = $request->file('id_proof_back');
            $backExt = $backFile->getClientOriginalExtension();
            $backFilename = "{$userId}_verification_back_{$timestamp}.{$backExt}";
            $backPath = $backFile->storeAs('id_proofs', $backFilename, 'public');
        }

        $verification = UserVerification::updateOrCreate(
            ['user_id' => $user->id],
            [
                'id_proof_type' => $request->id_proof_type,
                'id_proof_number' => $request->id_proof_number,
                'id_proof_front_url' => Storage::url($frontPath),
                'id_proof_back_url' => $backPath ? Storage::url($backPath) : null,
                'status' => 'pending',
                'rejection_reason' => null,
            ]
        );

        return response()->json([
            'message' => 'ID proof submitted successfully',
            'verification' => $verification
        ]);
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
