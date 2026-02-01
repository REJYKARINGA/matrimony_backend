<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'account_name',
        'account_number',
        'ifsc_code',
        'razorpay_fund_account_id',
        'is_primary'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
