<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EngagementPoster;

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
            'poster_image' => 'nullable|string',
            'engagement_date' => 'required|date',
            'announcement_title' => 'required|string|max:255',
            'announcement_message' => 'required|string',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'display_expire_at' => 'required|date',
        ]);

        $request->merge(['user_id' => auth()->id()]);

        $poster = EngagementPoster::create($request->all());

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
            'poster_image' => 'nullable|string',
            'engagement_date' => 'date',
            'announcement_title' => 'string|max:255',
            'announcement_message' => 'string',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'display_expire_at' => 'date',
        ]);

        $poster->update($request->all());

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
