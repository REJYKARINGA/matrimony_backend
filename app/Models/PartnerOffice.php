<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerOffice extends Model
{
    use SoftDeletes;

    protected $table = 'partner_offices';

    protected $fillable = [
        'name',
        'office_code',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'commission_per_registration',
        'revenue_share_percent',
        'status',
        'logo',
        'created_by',
    ];

    protected $casts = [
        'commission_per_registration' => 'decimal:2',
        'revenue_share_percent' => 'decimal:2',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(PartnerAgent::class, 'partner_office_id');
    }

    public function activeAgents(): HasMany
    {
        return $this->hasMany(PartnerAgent::class, 'partner_office_id')->where('status', 'active');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(Reference::class, 'partner_office_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(PartnerPayout::class, 'partner_office_id');
    }

    public static function generateOfficeCode(): string
    {
        do {
            $code = 'PO' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
        } while (static::where('office_code', $code)->exists());

        return $code;
    }
}
