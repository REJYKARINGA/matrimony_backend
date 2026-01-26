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

        // Calculate user age for global ceiling (not showing older than user)
        $userAge = null;
        if ($user->userProfile && $user->userProfile->date_of_birth) {
            $userAge = \Carbon\Carbon::parse($user->userProfile->date_of_birth)->age;
        }

        // Define which fields we want to show as cards
        $fields = [
            'religion' => ['title' => 'Religion Match', 'icon' => 'religion'],
            'caste' => ['title' => 'Caste Match', 'icon' => 'caste'],
            'occupation' => ['title' => 'Occupation Match', 'icon' => 'work'],
            'education' => ['title' => 'Education Match', 'icon' => 'school'],
            'marital_status' => ['title' => 'Marital Status Match', 'icon' => 'heart'],
        ];

        foreach ($fields as $field => $meta) {
            $value = $preferences->$field ?? $user->userProfile->$field ?? null;
            if ($value) {
                $count = User::whereHas('userProfile', function ($q) use ($field, $value, $user, $userAge) {
                    if ($field === 'caste' && is_array($value)) {
                        $q->whereIn($field, $value);
                    } else {
                        $q->where($field, $value);
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
            $radius = 50; // 50km

            $count = User::join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                ->where('users.id', '!=', $user->id)
                ->where('users.status', 'active')
                ->whereHas('userProfile', function ($q) use ($userAge) {
                    $q->where('is_active_verified', true);
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
                    'title' => 'Near Me (GPS)',
                    'value' => 'Within 50 km',
                    'count' => $count,
                    'icon' => 'near_me'
                ];
            }
        }

        // Height Match
        if ($preferences->min_height || $preferences->max_height) {
            $count = User::whereHas('userProfile', function ($q) use ($preferences, $user, $userAge) {
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

        // Calculate user age for global ceiling
        $userAge = null;
        if ($user->userProfile && $user->userProfile->date_of_birth) {
            $userAge = \Carbon\Carbon::parse($user->userProfile->date_of_birth)->age;
        }

        $query = User::with(['userProfile', 'profilePhotos'])
            ->where('id', '!=', $user->id)
            ->where('status', 'active')
            ->whereHas('userProfile', function ($q) use ($userAge, $request) {
                $q->where('is_active_verified', true);

                // UNLESS this is a specific age search, don't show older than me
                if ($userAge && $request->field != 'age' && !$request->filled('min_age') && !$request->filled('max_age')) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) <= ?', [$userAge]);
                }
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
            $casteValue = $request->caste;
            $query->whereHas('userProfile', function ($q) use ($casteValue) {
                if (is_array($casteValue)) {
                    $q->whereIn('caste', $casteValue);
                } else if (is_string($casteValue) && str_contains($casteValue, ',')) {
                    $q->whereIn('caste', explode(',', $casteValue));
                } else {
                    $q->where('caste', $casteValue);
                }
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

        if ($request->filled('field') && $request->field == 'new_members') {
            $query->where('users.created_at', '>=', now()->subDays(7));
        }

        if ($request->filled('field') && $request->field == 'mother_tongue') {
            $query->whereHas('userProfile', function ($q) use ($user) {
                $q->where('mother_tongue', $user->userProfile->mother_tongue);
            });
        }

        if ($request->filled('field') && $request->field == 'income') {
            $query->whereHas('userProfile', function ($q) use ($user) {
                $q->where('annual_income', '>=', $user->preferences->min_income ?? 0);
            });
        }

        if ($request->filled('field') && $request->field == 'height') {
            $query->whereHas('userProfile', function ($q) use ($user) {
                if ($user->preferences->min_height)
                    $q->where('height', '>=', $user->preferences->min_height);
                if ($user->preferences->max_height)
                    $q->where('height', '<=', $user->preferences->max_height);
            });
        }

        $profiles = $query->paginate(20);

        return response()->json([
            'profiles' => $profiles
        ]);
    }
}
