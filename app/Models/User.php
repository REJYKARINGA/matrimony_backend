<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\UserMatch;
use App\Models\UserProfile;
use App\Models\FamilyDetail;
use App\Models\Preference;
use App\Models\ProfilePhoto;
use App\Models\InterestSent;
use App\Models\Message;
use App\Models\ProfileView;
use App\Models\ShortlistedProfile;
use App\Models\BlockedUser;
use App\Models\UserSubscription;
use App\Models\Payment;
use App\Models\Report;
use App\Models\SuccessStory;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\AdminPermission;
use App\Models\EngagementPoster;
use App\Models\Suggestion;
use App\Models\UserVerification;

use Laravel\Sanctum\HasApiTokens;

class User extends Model
{
    use HasApiTokens, SoftDeletes;

    protected $table = 'users';
    protected $fillable = [
        'matrimony_id',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'email_verified',
        'phone_verified',
        'last_login',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            if (!$user->matrimony_id) {
                $user->matrimony_id = static::generateMatrimonyId();
            }
        });
    }

    /**
     * Generate a unique matrimony ID
     */
    public static function generateMatrimonyId()
    {
        $prefix = 'VM';
        $number = rand(100000, 999999);
        $matrimonyId = $prefix . $number;

        // Check if exists and regenerate if necessary
        while (static::where('matrimony_id', $matrimonyId)->exists()) {
            $number = rand(100000, 999999);
            $matrimonyId = $prefix . $number;
        }

        return $matrimonyId;
    }

    protected $casts = [
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'last_login' => 'datetime',
    ];

    /**
     * Relationship with user profile
     */
    public function userProfile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    /**
     * Relationship with family details
     */
    public function familyDetails(): HasOne
    {
        return $this->hasOne(FamilyDetail::class, 'user_id');
    }

    /**
     * Relationship with preferences
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(Preference::class, 'user_id');
    }

    /**
     * Relationship with profile photos
     */
    public function profilePhotos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class, 'user_id');
    }

    /**
     * Relationship with interests sent
     */
    public function interestsSent()
    {
        return $this->hasMany('App\Models\InterestSent', 'sender_id');
    }

    /**
     * Relationship with received interests
     */
    public function interestsReceived()
    {
        return $this->hasMany('App\Models\InterestSent', 'receiver_id');
    }

    /**
     * Relationship with matches (as user1)
     */
    public function matchesAsUser1()
    {
        return $this->hasMany('App\Models\UserMatch', 'user1_id');
    }

    /**
     * Relationship with matches (as user2)
     */
    public function matchesAsUser2()
    {
        return $this->hasMany('App\Models\UserMatch', 'user2_id');
    }

    /**
     * Relationship with sent messages
     */
    public function sentMessages()
    {
        return $this->hasMany('App\Models\Message', 'sender_id');
    }

    /**
     * Relationship with received messages
     */
    public function receivedMessages()
    {
        return $this->hasMany('App\Models\Message', 'receiver_id');
    }

    /**
     * Relationship with profile views (as viewer)
     */
    public function profileViews()
    {
        return $this->hasMany('App\Models\ProfileView', 'viewer_id');
    }

    /**
     * Relationship with shortlisted profiles
     */
    public function shortlistedProfiles()
    {
        return $this->hasMany('App\Models\ShortlistedProfile', 'user_id');
    }

    /**
     * Relationship with blocked users (by this user)
     */
    public function blockedUsers()
    {
        return $this->hasMany('App\Models\BlockedUser', 'user_id');
    }

    /**
     * Relationship with user subscriptions
     */
    public function userSubscriptions()
    {
        return $this->hasMany('App\Models\UserSubscription', 'user_id');
    }

    /**
     * Relationship with payments
     */
    public function payments()
    {
        return $this->hasMany('App\Models\Payment', 'user_id');
    }

    /**
     * Relationship with reports (as reporter)
     */
    public function reports()
    {
        return $this->hasMany('App\Models\Report', 'reporter_id');
    }

    /**
     * Relationship with reported by this user
     */
    public function reportedUsers()
    {
        return $this->hasMany('App\Models\Report', 'reported_user_id');
    }

    /**
     * Relationship with success stories (as user1)
     */
    public function successStoriesAsUser1()
    {
        return $this->hasMany('App\Models\SuccessStory', 'user1_id');
    }

    /**
     * Relationship with success stories (as user2)
     */
    public function successStoriesAsUser2()
    {
        return $this->hasMany('App\Models\SuccessStory', 'user2_id');
    }

    /**
     * Relationship with notifications
     */
    public function notifications()
    {
        return $this->hasMany('App\Models\Notification', 'user_id');
    }

    /**
     * Relationship with activity logs
     */
    public function activityLogs()
    {
        return $this->hasMany('App\Models\ActivityLog', 'user_id');
    }

    /**
     * Relationship with admin permissions
     */
    public function adminPermissions()
    {
        return $this->hasMany('App\Models\AdminPermission', 'user_id');
    }

    /**
     * Relationship with engagement posters
     */
    public function engagementPosters()
    {
        return $this->hasMany(EngagementPoster::class);
    }

    /**
     * Relationship with suggestions
     */
    public function suggestions()
    {
        return $this->hasMany(Suggestion::class);
    }

    /**
     * Relationship with responded suggestions
     */
    public function respondedSuggestions()
    {
        return $this->hasMany(Suggestion::class, 'responded_by');
    }
    /**
     * Relationship with user verification
     */
    public function verification(): HasOne
    {
        return $this->hasOne(UserVerification::class, 'user_id');
    }
}

