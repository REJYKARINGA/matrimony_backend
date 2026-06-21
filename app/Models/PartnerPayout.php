<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPayout extends Model
{
    protected $table = 'partner_payouts';

    protected $fillable = [
        'partner_office_id',
        'amount',
        'status',
        'transfer_id',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(PartnerOffice::class, 'partner_office_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
