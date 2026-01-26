<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Preference;

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

        // Define which fields we want to show as cards
        $fields = [
            'religion' => ['title' => 'Religion Match', 'icon' => 'religion'],
            'caste' => ['title' => 'Caste Match', 'icon' => 'caste'],
            'occupation' => ['title' => 'Occupation Match', 'icon' => 'work'],
            'education' => ['title' => 'Education Match', 'icon' => 'school'],
            'marital_status' => ['title' => 'Marital Status Match', 'icon' => 'heart'],
        ];

        foreach ($fields as $field => $meta) {
            $value = $preferences->$field;
            if ($value) {
                $count = User::whereHas('userProfile', function ($q) use ($field, $value, $user) {
                    $q->where($field, $value);

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
                        'field' => $field,
                        'title' => $meta['title'],
                        'value' => $value,
                        'count' => $count,
                        'icon' => $meta['icon']
                    ];
                }
            }
        }

        // Age Match
        if ($preferences->min_age || $preferences->max_age) {
            $count = User::whereHas('userProfile', function ($q) use ($preferences, $user) {
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
            $count = User::whereHas('userProfile', function ($q) use ($locations, $user) {
                $q->whereIn('district', $locations);

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
            $count = User::whereHas('userProfile', function ($q) use ($myDistrict, $user) {
                $q->where('district', $myDistrict);

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
            $radius = 50; // 50km

            $count = User::join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->where('users.id', '!=', $user->id)
                ->where('users.status', 'active')
                ->whereHas('userProfile', function ($q) {
                    $q->where('is_active_verified', true);
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
                    'title' => 'Near Me (GPS)',
                    'value' => 'Within 50 km',
                    'count' => $count,
                    'icon' => 'near_me'
                ];
            }
        }

        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Search profiles with filters
     */
    public function search(Request $request)
    {
        $user = $request->user();
        $query = User::with(['userProfile', 'profilePhotos'])
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->whereHas('userProfile', function ($q) {
                $q->where('is_active_verified', true);
            });

        // Filter by gender (show opposite gender)
        $userProfile = $user->userProfile;
        if ($userProfile && $userProfile->gender) {
            $oppositeGender = $userProfile->gender === 'male' ? 'female' : ($userProfile->gender === 'female' ? 'male' : null);
            if ($oppositeGender) {
                $query->whereHas('userProfile', function ($q) use ($oppositeGender) {
                    $q->where('gender', $oppositeGender);
                });
            }
        }

        if ($request->filled('religion')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('religion', $request->religion);
            });
        }

        if ($request->filled('caste')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('caste', $request->caste);
            });
        }

        if ($request->filled('occupation')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('occupation', $request->occupation);
            });
        }

        if ($request->filled('education')) {
            $query->whereHas('userProfile', function ($q) use ($request) {
                $q->where('education', $request->education);
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

        $profiles = $query->paginate(20);

        return response()->json([
            'profiles' => $profiles
        ]);
    }
}
