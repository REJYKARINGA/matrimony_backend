<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerAgent extends Model
{
    use SoftDeletes;

    protected $table = 'partner_agents';

    protected $fillable = [
        'partner_office_id',
        'user_id',
        'name',
        'phone',
        'email',
        'status',
    ];

    public function office(): BelongsTo
    {
        return $this->belongsTo(PartnerOffice::class, 'partner_office_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
