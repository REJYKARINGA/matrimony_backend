<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Preference;
use App\Models\DiscoveryStat;
use App\Http\Resources\UserResource;

class SearchController extends Controller
{
    /**
     * Get summary of matches for each preference field
     */
    public function getPreferenceMatches(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences;

        if (!$preferences) {
            $preferences = new \App\Models\Preference();
        }

        $categories = [];

        // Fetch click stats for sorting
        $stats = DiscoveryStat::pluck('click_count', 'category')->toArray();

        // Calculate user age for global ceiling (not showing older than user)
        $userAge = null;
        if ($user->userProfile && $user->userProfile->date_of_birth) {
            $userAge = \Carbon\Carbon::parse($user->userProfile->date_of_birth)->age;
        }

        // Define which fields we want to show as cards
        $fields = [
            'caste' => ['title' => 'Caste Match', 'icon' => 'caste'],
            'occupation' => ['title' => 'Occupation Match', 'icon' => 'work'],
            'education' => ['title' => 'Education Match', 'icon' => 'school'],
            'marital_status' => ['title' => 'Marital Status Match', 'icon' => 'heart'],
        ];

        foreach ($fields as $field => $meta) {
            $value = $preferences->$field ?? $user->userProfile->$field ?? null;
            if ($value) {
                $count = User::whereHas('userProfile', function ($q) use ($field, $value, $user, $userAge) {
                    $q->where('is_active_verified', true);
                    if ($user->userProfile && $user->userProfile->religion_id) {
                        $q->where('religion_id', $user->userProfile->religion_id);
                    }
                    if ($field === 'caste' && is_array($value)) {
                        $q->whereIn('caste_id', $value);
                    } elseif ($field === 'marital_status') {
                        $q->where('marital_status', $value);
                    } else {
                        $q->where($field . '_id', $value);
                    }

                    if ($userAge) {
                        $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                    }

                    // Filter by gender
                    $userProfile = $user->userProfile;
                    if ($userProfile && $userProfile->gender) {
                        $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                        if ($oppositeGender) {
                            $q->where('gender', $oppositeGender);
                        }
                    }
                })
                    ->where('id', '!=', $user->id)
                    ->where('status', 'active')
                    ->count();

                if ($count > 0) {
                    $valDisplay = $value;
                    if ($field === 'caste' && is_array($value)) {
                        $valDisplay = implode(', ', array_slice($value, 0, 2)) . (count($value) > 2 ? '...' : '');
                    }
                    $categories[] = [
                        'field' => $field,
                        'title' => $meta['title'],
                        'value' => $valDisplay,
                        'count' => $count,
                        'icon' => $meta['icon']
                    ];
                }
            }
        }

        // Age Match
        if ($preferences->min_age || $preferences->max_age) {
            $count = User::whereHas('userProfile', function ($q) use ($preferences, $user) {
                $q->where('is_active_verified', true);
                if ($user->userProfile && $user->userProfile->religion_id) {
                    $q->where('religion_id', $user->userProfile->religion_id);
                }
                if ($preferences->min_age) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$preferences->min_age]);
                }
                if ($preferences->max_age) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$preferences->max_age]);
                }

                // Filter by gender
                $userProfile = $user->userProfile;
                if ($userProfile && $userProfile->gender) {
                    $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                    if ($oppositeGender) {
                        $q->where('gender', $oppositeGender);
                    }
                }
            })
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->count();

            if ($count > 0) {
                $categories[] = [
                    'field' => 'age',
                    'title' => 'Age Match',
                    'value' => ($preferences->min_age ?? 'Any') . ' - ' . ($preferences->max_age ?? 'Any') . ' Years',
                    'count' => $count,
                    'icon' => 'calendar'
                ];
            }
        }

        // Location Match (based on District)
        if (is_array($preferences->preferred_locations) && count($preferences->preferred_locations) > 0) {
            $locations = $preferences->preferred_locations;
            $count = User::whereHas('userProfile', function ($q) use ($locations, $user, $userAge) {
                $q->where('is_active_verified', true);
                if ($user->userProfile && $user->userProfile->religion_id) {
                    $q->where('religion_id', $user->userProfile->religion_id);
                }
                $q->whereIn('district', $locations);

                if ($userAge) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }

                // Filter by gender
                $userProfile = $user->userProfile;
                if ($userProfile && $userProfile->gender) {
                    $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                    if ($oppositeGender) {
                        $q->where('gender', $oppositeGender);
                    }
                }
            })
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->count();

            if ($count > 0) {
                $categories[] = [
                    'field' => 'location',
                    'title' => 'District Match',
                    'value' => implode(', ', array_slice($locations, 0, 2)) . (count($locations) > 2 ? '...' : ''),
                    'count' => $count,
                    'icon' => 'location'
                ];
            }
        }

        // Additional: Same District Match (Even if not in preferences)
        $myDistrict = $user->userProfile->district ?? null;
        if ($myDistrict && (!is_array($preferences->preferred_locations) || !in_array($myDistrict, $preferences->preferred_locations))) {
            $count = User::whereHas('userProfile', function ($q) use ($myDistrict, $user, $userAge) {
                $q->where('is_active_verified', true);
                if ($user->userProfile && $user->userProfile->religion_id) {
                    $q->where('religion_id', $user->userProfile->religion_id);
                }
                $q->where('district', $myDistrict);

                if ($userAge) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }

                // Filter by gender
                $userProfile = $user->userProfile;
                if ($userProfile && $userProfile->gender) {
                    $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                    if ($oppositeGender) {
                        $q->where('gender', $oppositeGender);
                    }
                }
            })
                ->where('id', '!=', $user->id)
                ->where('status', 'active')
                ->count();

            if ($count > 0) {
                $categories[] = [
                    'field' => 'near_me',
                    'title' => 'Near Me (District)',
                    'value' => $myDistrict,
                    'count' => $count,
                    'icon' => 'near_me'
                ];
            }
        }

        // GPS Near Me (Dynamic)
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;
            $radius = $preferences->max_distance ?? 50; // Use preference or default 50km

            $count = User::join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->where('users.id', '!=', $user->id)
                ->where('users.status', 'active')
                ->whereHas('userProfile', function ($q) use ($user, $userAge) {
                    $q->where('is_active_verified', true);
                    if ($user->userProfile && $user->userProfile->religion_id) {
                        $q->where('religion_id', $user->userProfile->religion_id);
                    }
                    if ($userAge) {
                        $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                    }
                })
                ->whereNotNull('user_profiles.latitude')
                ->whereNotNull('user_profiles.longitude')
                ->selectRaw("(6371 * acos(cos(radians(?)) * cos(radians(user_profiles.latitude)) * cos(radians(user_profiles.longitude) - radians(?)) + sin(radians(?)) * sin(radians(user_profiles.latitude)))) AS distance", [$lat, $lon, $lat])
                ->having('distance', '<=', $radius);

            // Filter by gender
            if ($user->userProfile->gender) {
                $oppositeGender = $user->userProfile->gender === 'male' ? 'female' : ($user->userProfile->gender === 'female' ? 'male' : null);
                if ($oppositeGender) {
                    $count->where('user_profiles.gender', $oppositeGender);
                }
            }

            $count = $count->count();

            if ($count > 0) {
                $categories[] = [
                    'field' => 'near_me_gps',
                    'title' => 'Near Me (Distance)',
                    'value' => "Within $radius km",
                    'count' => $count,
                    'icon' => 'near_me'
                ];
            }
        }

        // Height Match
        if ($preferences->min_height || $preferences->max_height) {
            $count = User::whereHas('userProfile', function ($q) use ($preferences, $user, $userAge) {
                $q->where('is_active_verified', true);
                if ($user->userProfile && $user->userProfile->religion_id) {
                    $q->where('religion_id', $user->userProfile->religion_id);
                }
                if ($preferences->min_height)
                    $q->where('height', '>=', $preferences->min_height);
                if ($preferences->max_height)
                    $q->where('height', '<=', $preferences->max_height);

                if ($userAge) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }

                $userProfile = $user->userProfile;
                if ($userProfile && $userProfile->gender) {
                    $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                    if ($oppositeGender)
                        $q->where('gender', $oppositeGender);
                }
            })->where('status', 'active')->where('id', '!=', $user->id)->count();

            if ($count > 0) {
                $categories[] = [
                    'field' => 'height',
                    'title' => 'Height Match',
                    'value' => ($preferences->min_height ?? 'Any') . ' - ' . ($preferences->max_height ?? 'Any') . ' cm',
                    'count' => $count,
                    'icon' => 'height'
                ];
            }
        }

        // Income Match
        if ($preferences->min_income) {
            $count = User::whereHas('userProfile', function ($q) use ($preferences, $user, $userAge) {
                $q->where('is_active_verified', true);
                if ($user->userProfile && $user->userProfile->religion_id) {
                    $q->where('religion_id', $user->userProfile->religion_id);
                }
                $q->where('annual_income', '>=', $preferences->min_income);

                if ($userAge) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }

                $userProfile = $user->userProfile;
                if ($userProfile && $userProfile->gender) {
                    $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                    if ($oppositeGender)
                        $q->where('gender', $oppositeGender);
                }
            })->where('status', 'active')->where('id', '!=', $user->id)->count();

            if ($count > 0) {
                $categories[] = [
                    'field' => 'income',
                    'title' => 'Income Match',
                    'value' => 'Above ₹' . number_format($preferences->min_income / 100000, 1) . ' Lakh',
                    'count' => $count,
                    'icon' => 'payments'
                ];
            }
        }

        // Mother Tongue Match
        $myTongue = $user->userProfile->mother_tongue ?? null;
        if ($myTongue) {
            $count = User::whereHas('userProfile', function ($q) use ($myTongue, $user, $userAge) {
                $q->where('is_active_verified', true);
                if ($user->userProfile && $user->userProfile->religion_id) {
                    $q->where('religion_id', $user->userProfile->religion_id);
                }
                $q->where('mother_tongue', $myTongue);

                if ($userAge) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }

                $userProfile = $user->userProfile;
                if ($userProfile && $userProfile->gender) {
                    $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                    if ($oppositeGender)
                        $q->where('gender', $oppositeGender);
                }
            })->where('status', 'active')->where('id', '!=', $user->id)->count();

            if ($count > 0) {
                $categories[] = [
                    'field' => 'mother_tongue',
                    'title' => 'Language Match',
                    'value' => $myTongue,
                    'count' => $count,
                    'icon' => 'translate'
                ];
            }
        }

        // New Members (Joined in last 7 days)
        $count = User::where('created_at', '>=', now()->subDays(7))
            ->whereHas('userProfile', function ($q) use ($user, $userAge) {
                $q->where('is_active_verified', true);
                if ($user->userProfile && $user->userProfile->religion_id) {
                    $q->where('religion_id', $user->userProfile->religion_id);
                }
                if ($userAge) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }

                $userProfile = $user->userProfile;
                if ($userProfile && $userProfile->gender) {
                    $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
                    if ($oppositeGender)
                        $q->where('gender', $oppositeGender);
                }
            })->where('status', 'active')->where('id', '!=', $user->id)->count();

        if ($count > 0) {
            $categories[] = [
                'field' => 'new_members',
                'title' => 'New Members',
                'value' => 'Joined this week',
                'count' => $count,
                'icon' => 'person_add'
            ];
        }

        // --- TRENDING SORTING LOGIC ---
        // Sort categories based on click stats (Higher counts first)
        usort($categories, function ($a, $b) use ($stats) {
            $countA = $stats[$a['field']] ?? 0;
            $countB = $stats[$b['field']] ?? 0;

            // If counts are equal, keep existing order
            if ($countA == $countB)
                return 0;

            return ($countA > $countB) ? -1 : 1;
        });

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Log a click on a discovery card for trending sorting
     */
    public function logDiscoveryClick(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:255'
        ]);

        $stat = DiscoveryStat::firstOrCreate(
            ['category' => $request->category],
            ['click_count' => 0]
        );

        $stat->increment('click_count');

        return response()->json(['success' => true]);
    }

    /**
     * Search profiles with filters
     */
    public function search(Request $request)
    {
        $user = $request->user();

        // Calculate user age for global ceiling
        $userAge = null;
        if ($user->userProfile && $user->userProfile->date_of_birth) {
            $userAge = \Carbon\Carbon::parse($user->userProfile->date_of_birth)->age;
        }

        $isIdSearch = $request->filled('matrimony_id');
        $userProfile = $user->userProfile;

        $query = User::with([
            'userProfile.religionModel',
            'userProfile.casteModel',
            'userProfile.subCasteModel',
            'userProfile.educationModel',
            'userProfile.occupationModel',
            'profilePhotos' => function ($q) {
                $q->where('is_primary', true)->limit(1);
            }
        ])
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->whereHas('userProfile', function ($q) use ($userAge, $request, $isIdSearch, $userProfile) {
                $q->where('is_active_verified', true);

                // UNLESS this is a specific age search or ID search, don't show older than me
                if (!$isIdSearch && $userAge && $request->field != 'age' && !$request->filled('min_age') && !$request->filled('max_age')) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }

                // Strictly enforce same religion
                if ($userProfile && $userProfile->religion_id) {
                    $q->where('religion_id', $userProfile->religion_id);
                }
            });

        if ($isIdSearch) {
            $query->where('matrimony_id', 'LIKE', '%' . $request->matrimony_id . '%');
        }

        // Filter by gender (show opposite gender) - Strictly enforced
        if ($userProfile && $userProfile->gender) {
            $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
            if ($oppositeGender) {
                $query->whereHas('userProfile', function ($q) use ($oppositeGender) {
                    $q->where('gender', $oppositeGender);
                });
            }
        }

        if ($request->filled('religion_id')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('religion_id', $request->religion_id);
            });
        }

        if ($request->filled('caste_id')) {
            $casteValue = $request->caste_id;
            $query->whereHas('userProfile', function ($q) use ($casteValue) {
                if (is_array($casteValue)) {
                    $q->whereIn('caste_id', $casteValue);
                } else if (is_string($casteValue) && str_contains($casteValue, ',')) {
                    $q->whereIn('caste_id', explode(',', $casteValue));
                } else {
                    $q->where('caste_id', $casteValue);
                }
            });
        }

        if ($request->filled('occupation_id')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('occupation_id', $request->occupation_id);
            });
        }

        if ($request->filled('education_id')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('education_id', $request->education_id);
            });
        }

        if ($request->filled('marital_status')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('marital_status', $request->marital_status);
            });
        }

        if ($request->filled('min_age')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= ?', [$request->min_age]);
            });
        }

        if ($request->filled('max_age')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$request->max_age]);
            });
        }

        if ($request->filled('min_height')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('height', '>=', $request->min_height);
            });
        }

        if ($request->filled('max_height')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('height', '<=', $request->max_height);
            });
        }

        if ($request->filled('location')) {
            $location = $request->location;
            $query->whereHas('userProfile', function ($q) use ($location, $user) {
                // If it's the "District Match" card (which might have "Location1, Location2..." truncated)
                // we should check against the user's actual preferred locations
                if (str_contains($location, '...') || str_contains($location, ',')) {
                    $prefDistricts = $user->preferences->preferred_locations ?? [];
                    if (!empty($prefDistricts)) {
                        $q->whereIn('district', $prefDistricts);
                    } else {
                        $q->where('district', 'LIKE', "%{$location}%");
                    }
                } else {
                    $q->where('district', 'LIKE', "%{$location}%");
                }
            });
        }

        if ($request->filled('field') && $request->field == 'matching_me') {
            $userAge = $userProfile ? \Carbon\Carbon::parse($userProfile->date_of_birth)->age : 25;
            $userHeight = $userProfile?->height;
            $userMarital = $userProfile?->marital_status;
            $userReligion = $userProfile?->religion_id;
            $userCaste = $userProfile?->caste_id;
            $userDistrict = $userProfile?->district;

            $query->whereHas('preferences', function ($q) use ($userAge, $userHeight, $userMarital, $userReligion, $userCaste, $userDistrict) {
                // Age range check
                $q->where(function ($sub) use ($userAge) {
                    $sub->whereNull('min_age')->orWhere('min_age', '<=', $userAge);
                })->where(function ($sub) use ($userAge) {
                    $sub->whereNull('max_age')->orWhere('max_age', '>=', $userAge);
                });

                // Height range check
                if ($userHeight) {
                    $q->where(function ($sub) use ($userHeight) {
                        $sub->whereNull('min_height')->orWhere('min_height', '<=', $userHeight);
                    })->where(function ($sub) use ($userHeight) {
                        $sub->whereNull('max_height')->orWhere('max_height', '>=', $userHeight);
                    });
                }

                // Marital Status check
                if ($userMarital) {
                    $q->where(function ($sub) use ($userMarital) {
                        $sub->whereNull('marital_status')->orWhere('marital_status', $userMarital);
                    });
                }

                // Religion check
                if ($userReligion) {
                    $q->where(function ($sub) use ($userReligion) {
                        $sub->whereNull('religion_id')->orWhere('religion_id', $userReligion);
                    });
                }

                // Caste check (JSON array)
                if ($userCaste) {
                    $q->where(function ($sub) use ($userCaste) {
                        $sub->whereNull('caste_ids')->orWhereJsonContains('caste_ids', (int) $userCaste);
                    });
                }

                // District check (JSON array)
                if ($userDistrict) {
                    $q->where(function ($sub) use ($userDistrict) {
                        $sub->whereNull('preferred_locations')->orWhereJsonContains('preferred_locations', $userDistrict);
                    });
                }
            });
        }

        $profiles = $query->paginate(20);

        // Add distance calculation
        if ($user->userProfile && $user->userProfile->latitude && $user->userProfile->longitude) {
            $lat = $user->userProfile->latitude;
            $lon = $user->userProfile->longitude;

            $profiles->getCollection()->transform(function ($profile) use ($lat, $lon) {
                if ($profile->userProfile && $profile->userProfile->latitude) {
                    $profile->distance = $this->calculateDistance(
                        $lat,
                        $lon,
                        $profile->userProfile->latitude,
                        $profile->userProfile->longitude
                    );
                }
                return $profile;
            });
        }

        return response()->json([
            'profiles' => UserResource::collection($profiles)->response()->getData(true)
        ]);
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
