<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reference extends Model
{
    use SoftDeletes;

    protected $table = 'references';

    protected $fillable = [
        'referenced_by_id',
        'referenced_user_id',
        'reference_code',
        'reference_type',
        'purchased_count',
        'total_paid_amount',
    ];

    protected $casts = [
        'purchased_count' => 'integer',
    ];

    /**
     * The mediator / person who shared the code.
     */
    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referenced_by_id');
    }

    /**
     * The user who used the code at registration.
     */
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referenced_user_id');
    }
}
