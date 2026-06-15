<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_contact_unlock_limit',
        'contact_unlock_price',
        'user_contact_permission_unlock',
        'mandatory_permission_for_unlock',
        'free_unlock_enabled',
        'free_unlock_expires_at',
    ];

    protected $casts = [
        'daily_contact_unlock_limit' => 'integer',
        'contact_unlock_price' => 'decimal:2',
        'user_contact_permission_unlock' => 'boolean',
        'mandatory_permission_for_unlock' => 'boolean',
        'free_unlock_enabled' => 'boolean',
        'free_unlock_expires_at' => 'datetime',
    ];

    public function isFreeUnlockActive(): bool
    {
        if (!$this->free_unlock_enabled) {
            return false;
        }
        if ($this->free_unlock_expires_at && now()->greaterThan($this->free_unlock_expires_at)) {
            return false;
        }
        return true;
    }

    public function getUnlockPrice(): float
    {
        return (float) ($this->contact_unlock_price ?? 49.00);
    }

    public function getDiscountedPrice(?string $gender = null): float
    {
        $basePrice = $this->getUnlockPrice();
        $result = Festival::getBestActiveDiscount($basePrice, $gender);
        return $result['discounted_price'];
    }

    public function getRechargeTiers(): array
    {
        return RechargeTier::where('is_active', true)
            ->orderBy('priority_order')
            ->get()
            ->map(fn($tier) => [
                'amount' => (int) $tier->amount,
                'contacts' => $tier->contacts,
            ])
            ->toArray();
    }
}
