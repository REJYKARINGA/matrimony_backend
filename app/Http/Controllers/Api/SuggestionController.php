<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Suggestion;

class SuggestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $suggestions = Suggestion::with('user', 'responder')->paginate(15);

        return response()->json([
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_photos' => 'nullable|array|max:3',
            'user_photos.*' => 'string',
        ]);

        $request->merge(['user_id' => auth()->id()]);

        $suggestion = Suggestion::create($request->all());

        return response()->json([
            'suggestion' => $suggestion->load('user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $suggestion = Suggestion::with('user', 'responder')->find($id);

        if (!$suggestion) {
            return response()->json([
                'error' => 'Suggestion not found'
            ], 404);
        }

        return response()->json([
            'suggestion' => $suggestion
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return response()->json([
                'error' => 'Suggestion not found'
            ], 404);
        }

        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed,rejected',
            'response_text' => 'nullable|string',
            'response_photo' => 'nullable|string',
            'responded_at' => 'nullable|date',
            'responded_by' => 'nullable|integer|exists:users,id',
            'user_photos' => 'nullable|array|max:3',
            'user_photos.*' => 'string',
        ]);

        $suggestion->update($request->all());

        return response()->json([
            'suggestion' => $suggestion->load('user', 'responder')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $suggestion = Suggestion::find($id);

        if (!$suggestion) {
            return response()->json([
                'error' => 'Suggestion not found'
            ], 404);
        }

        $suggestion->delete();

        return response()->json([
            'message' => 'Suggestion deleted successfully'
        ]);
    }
}
