<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\Occupation;
use App\Models\Religion;
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
     * Get all active religion options with castes and sub-castes
     */
    public function getReligionOptions()
    {
        try {
            $religions = Religion::active()
                ->ordered()
                ->with([
                    'castes' => function ($query) {
                        $query->active()->ordered()->with([
                            'subCastes' => function ($q) {
                                $q->active()->ordered()->select('id', 'caste_id', 'name');
                            }
                        ])->select('id', 'religion_id', 'name');
                    }
                ])
                ->select('id', 'name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $religions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch religion options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all preference options (education, occupation, religion)
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

            $religions = Religion::active()
                ->ordered()
                ->with([
                    'castes' => function ($query) {
                        $query->active()->ordered()->with([
                            'subCastes' => function ($q) {
                                $q->active()->ordered()->select('id', 'caste_id', 'name');
                            }
                        ])->select('id', 'religion_id', 'name');
                    }
                ])
                ->select('id', 'name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'educations' => $educations,
                    'occupations' => $occupations,
                    'religions' => $religions
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
