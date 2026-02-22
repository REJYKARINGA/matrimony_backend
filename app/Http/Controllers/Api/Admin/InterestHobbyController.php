<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InterestHobby;

class InterestHobbyController extends Controller
{
    /**
     * Get all interests/hobbies with pagination and filters
     */
    public function getInterests(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);
            $search = $request->input('search');
            $type = $request->input('type');
            $isActive = $request->input('is_active');

            $query = InterestHobby::query();

            // Search filter
            if ($search) {
                $query->where('interest_name', 'like', "%{$search}%");
            }

            // Type filter
            if ($type) {
                $query->where('interest_type', $type);
            }

            // Active status filter
            if ($isActive !== null) {
                $query->where('is_active', filter_var($isActive, FILTER_VALIDATE_BOOLEAN));
            }

            // Order by trending number
            $query->orderBy('trending_number', 'asc');

            $interests = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'data' => $interests->items(),
                'total' => $interests->total(),
                'current_page' => $interests->currentPage(),
                'last_page' => $interests->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch interests',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all interest types (categories)
     */
    public function getInterestTypes()
    {
        try {
            $types = InterestHobby::select('interest_type')
                ->distinct()
                ->orderBy('interest_type')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $types->pluck('interest_type')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch interest types',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new interest/hobby
     */
    public function createInterest(Request $request)
    {
        try {
            $validated = $request->validate([
                'interest_name' => 'required|string|max:100|unique:interests_hobbies,interest_name',
                'interest_type' => 'required|string|max:50',
                'trending_number' => 'integer|min:0',
                'is_active' => 'boolean'
            ]);

            $interest = InterestHobby::create([
                'interest_name' => $validated['interest_name'],
                'interest_type' => $validated['interest_type'],
                'trending_number' => $validated['trending_number'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Interest created successfully',
                'data' => $interest
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
                'message' => 'Failed to create interest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an interest/hobby
     */
    public function updateInterest(Request $request, $id)
    {
        try {
            $interest = InterestHobby::findOrFail($id);

            $validated = $request->validate([
                'interest_name' => 'sometimes|required|string|max:100|unique:interests_hobbies,interest_name,' . $id,
                'interest_type' => 'sometimes|required|string|max:50',
                'trending_number' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean'
            ]);

            $interest->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Interest updated successfully',
                'data' => $interest
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
                'message' => 'Failed to update interest',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an interest/hobby
     */
    public function deleteInterest($id)
    {
        try {
            $interest = InterestHobby::findOrFail($id);
            $interest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Interest deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete interest',
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
                'interests' => 'required|array',
                'interests.*.id' => 'required|exists:interests_hobbies,id',
                'interests.*.trending_number' => 'required|integer|min:0'
            ]);

            foreach ($validated['interests'] as $item) {
                InterestHobby::where('id', $item['id'])
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
