<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Religion;
use App\Models\Caste;
use App\Models\SubCaste;

class ReligionController extends Controller
{
    // ==================== RELIGION METHODS ====================

    /**
     * Get all religions with pagination
     */
    public function getReligions(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);

            $religions = Religion::ordered()
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $religions->items(),
                'total' => $religions->total(),
                'current_page' => $religions->currentPage(),
                'last_page' => $religions->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch religions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new religion
     */
    public function createReligion(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
                'order_number' => 'integer|min:0'
            ]);

            $religion = Religion::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Religion created successfully',
                'data' => $religion
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
                'message' => 'Failed to create religion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a religion
     */
    public function updateReligion(Request $request, $id)
    {
        try {
            $religion = Religion::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'is_active' => 'sometimes|boolean',
                'order_number' => 'sometimes|integer|min:0'
            ]);

            $religion->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Religion updated successfully',
                'data' => $religion
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
                'message' => 'Failed to update religion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a religion
     */
    public function deleteReligion($id)
    {
        try {
            $religion = Religion::findOrFail($id);
            $religion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Religion deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete religion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== CASTE METHODS ====================

    /**
     * Get all castes with pagination
     */
    public function getCastes(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);

            $castes = Caste::with('religion')
                ->ordered()
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $castes->items(),
                'total' => $castes->total(),
                'current_page' => $castes->currentPage(),
                'last_page' => $castes->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch castes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new caste
     */
    public function createCaste(Request $request)
    {
        try {
            $validated = $request->validate([
                'religion_id' => 'required|exists:religions,id',
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
                'order_number' => 'integer|min:0'
            ]);

            $caste = Caste::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Caste created successfully',
                'data' => $caste
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
                'message' => 'Failed to create caste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a caste
     */
    public function updateCaste(Request $request, $id)
    {
        try {
            $caste = Caste::findOrFail($id);

            $validated = $request->validate([
                'religion_id' => 'sometimes|required|exists:religions,id',
                'name' => 'sometimes|required|string|max:255',
                'is_active' => 'sometimes|boolean',
                'order_number' => 'sometimes|integer|min:0'
            ]);

            $caste->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Caste updated successfully',
                'data' => $caste
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
                'message' => 'Failed to update caste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a caste
     */
    public function deleteCaste($id)
    {
        try {
            $caste = Caste::findOrFail($id);
            $caste->delete();

            return response()->json([
                'success' => true,
                'message' => 'Caste deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete caste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== SUBCASTE METHODS ====================

    /**
     * Get all sub-castes with pagination
     */
    public function getSubCastes(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            $page = $request->input('page', 1);

            $subCastes = SubCaste::with('caste')
                ->ordered()
                ->paginate($limit);

            return response()->json([
                'success' => true,
                'data' => $subCastes->items(),
                'total' => $subCastes->total(),
                'current_page' => $subCastes->currentPage(),
                'last_page' => $subCastes->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sub-castes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new sub-caste
     */
    public function createSubCaste(Request $request)
    {
        try {
            $validated = $request->validate([
                'caste_id' => 'required|exists:castes,id',
                'name' => 'required|string|max:255',
                'is_active' => 'boolean',
                'order_number' => 'integer|min:0'
            ]);

            $subCaste = SubCaste::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Sub-caste created successfully',
                'data' => $subCaste
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
                'message' => 'Failed to create sub-caste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a sub-caste
     */
    public function updateSubCaste(Request $request, $id)
    {
        try {
            $subCaste = SubCaste::findOrFail($id);

            $validated = $request->validate([
                'caste_id' => 'sometimes|required|exists:castes,id',
                'name' => 'sometimes|required|string|max:255',
                'is_active' => 'sometimes|boolean',
                'order_number' => 'sometimes|integer|min:0'
            ]);

            $subCaste->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Sub-caste updated successfully',
                'data' => $subCaste
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
                'message' => 'Failed to update sub-caste',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a sub-caste
     */
    public function deleteSubCaste($id)
    {
        try {
            $subCaste = SubCaste::findOrFail($id);
            $subCaste->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sub-caste deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sub-caste',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
