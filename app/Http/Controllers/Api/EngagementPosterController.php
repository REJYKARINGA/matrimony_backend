<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EngagementPoster;
use Illuminate\Support\Facades\Storage;

class EngagementPosterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posters = EngagementPoster::with('user')->paginate(15);

        return response()->json([
            'engagement_posters' => $posters
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'poster_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'engagement_date' => 'required|date',
            'announcement_title' => 'required|string|max:255',
            'announcement_message' => 'required|string',
            'is_active' => 'nullable|string', // Accept string from Multipart
            'is_verified' => 'nullable|string',
            'display_expire_at' => 'required|date',
        ]);

        $data = $request->except('poster_image');
        $data['user_id'] = auth()->id();

        // Handle Booleans from Multipart strings
        $data['is_active'] = filter_var($request->is_active ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['is_verified'] = filter_var($request->is_verified ?? false, FILTER_VALIDATE_BOOLEAN);

        // Handle Image Upload
        if ($request->hasFile('poster_image')) {
            $path = $request->file('poster_image')->store('engagement_posters', 'public');
            $data['poster_image'] = '/storage/' . $path;
        }

        $poster = EngagementPoster::create($data);

        return response()->json([
            'engagement_poster' => $poster->load('user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $poster = EngagementPoster::with('user')->find($id);

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        return response()->json([
            'engagement_poster' => $poster
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $poster = EngagementPoster::find($id);

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        $request->validate([
            'poster_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Allow file upload
            'engagement_date' => 'nullable|date', // Allow partial updates
            'announcement_title' => 'nullable|string|max:255',
            'announcement_message' => 'nullable|string',
            'is_active' => 'nullable|string', // Accept string from Multipart
            'is_verified' => 'nullable|string', // Accept string from Multipart
            'display_expire_at' => 'nullable|date',
        ]);

        $data = $request->except('poster_image');

        // Handle Booleans from Multipart strings if present
        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('is_verified')) {
            $data['is_verified'] = filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN);
        }

        // Handle Image Upload
        if ($request->hasFile('poster_image')) {
            // Delete old image if it exists
            if ($poster->poster_image) {
                $oldImagePath = str_replace('/storage/', '', $poster->poster_image);
                Storage::disk('public')->delete($oldImagePath);
            }
            $path = $request->file('poster_image')->store('engagement_posters', 'public');
            $data['poster_image'] = '/storage/' . $path;
        } elseif ($request->has('poster_image') && $request->poster_image === null) {
            // If poster_image is explicitly set to null, delete the old image
            if ($poster->poster_image) {
                $oldImagePath = str_replace('/storage/', '', $poster->poster_image);
                Storage::disk('public')->delete($oldImagePath);
            }
            $data['poster_image'] = null;
        }

        $poster->update($data);

        return response()->json([
            'engagement_poster' => $poster->load('user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $poster = EngagementPoster::find($id);

        if (!$poster) {
            return response()->json([
                'error' => 'Engagement poster not found'
            ], 404);
        }

        $poster->delete();

        return response()->json([
            'message' => 'Engagement poster deleted successfully'
        ]);
    }
}
