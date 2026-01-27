<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\Occupation;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    /**
     * Get all active education options
     */
    public function getEducationOptions()
    {
        try {
            $educations = Education::active()
                ->ordered()
                ->select('id', 'name', 'order_number', 'popularity_count')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $educations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch education options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active occupation options
     */
    public function getOccupationOptions()
    {
        try {
            $occupations = Occupation::active()
                ->ordered()
                ->select('id', 'name', 'order_number', 'popularity_count')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $occupations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch occupation options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all preference options (education and occupation)
     */
    public function getAllOptions()
    {
        try {
            $educations = Education::active()
                ->ordered()
                ->select('id', 'name', 'order_number', 'popularity_count')
                ->get();

            $occupations = Occupation::active()
                ->ordered()
                ->select('id', 'name', 'order_number', 'popularity_count')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'educations' => $educations,
                    'occupations' => $occupations
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch preference options',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
