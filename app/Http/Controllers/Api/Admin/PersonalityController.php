<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Personality;

class PersonalityController extends Controller
{
    /**
     * Get all personalities with pagination and filters
     */
    public function getPersonalities(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $type = $request->input('type');
            $isActive = $request->input('is_active');

            $query = Personality::query();

            // Search filter
            if ($search) {
                $query->where('personality_name', 'like', "%{$search}%");
            }

            // Type filter
            if ($type) {
                $query->where('personality_type', $type);
            }

            // Active status filter
            if ($isActive !== null) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            }

            // Order by trending number
            $query->orderBy('trending_number', 'asc');

            $personalities = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $personalities->items(),
                'total' => $personalities->total(),
                'current_page' => $personalities->currentPage(),
                'last_page' => $personalities->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch personalities',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all personality types (categories)
     */
    public function getPersonalityTypes()
    {
        try {
            $types = Personality::select('personality_type')
                ->distinct()
                ->orderBy('personality_type')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $types->pluck('personality_type')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch personality types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new personality
     */
    public function createPersonality(Request $request)
    {
        try {
            $validated = $request->validate([
                'personality_name' => 'required|string|max:100|unique:personalities,personality_name',
                'personality_type' => 'required|string|max:50',
                'trending_number' => 'integer|min:0',
                'is_active' => 'boolean'
            ]);

            $personality = Personality::create([
                'personality_name' => $validated['personality_name'],
                'personality_type' => $validated['personality_type'],
                'trending_number' => $validated['trending_number'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Personality created successfully',
                'data' => $personality
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create personality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a personality
     */
    public function updatePersonality(Request $request, $id)
    {
        try {
            $personality = Personality::findOrFail($id);

            $validated = $request->validate([
                'personality_name' => 'sometimes|required|string|max:100|unique:personalities,personality_name,' . $id,
                'personality_type' => 'sometimes|required|string|max:50',
                'trending_number' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            $personality->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Personality updated successfully',
                'data' => $personality
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update personality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a personality
     */
    public function deletePersonality($id)
    {
        try {
            $personality = Personality::findOrFail($id);
            $personality->delete();

            return response()->json([
                'success' => true,
                'message' => 'Personality deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete personality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update trending numbers
     */
    public function bulkUpdateTrending(Request $request)
    {
        try {
            $validated = $request->validate([
                'personalities' => 'required|array',
                'personalities.*.id' => 'required|exists:personalities,id',
                'personalities.*.trending_number' => 'required|integer|min:0'
            ]);

            foreach ($validated['personalities'] as $item) {
                Personality::where('id', $item['id'])
                    ->update(['trending_number' => $item['trending_number']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Trending numbers updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update trending numbers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
