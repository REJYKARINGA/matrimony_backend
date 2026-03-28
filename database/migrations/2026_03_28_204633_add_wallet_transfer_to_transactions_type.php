<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('wallet_recharge', 'contact_unlock', 'payout', 'usage_fee', 'wallet_transfer') NOT NULL DEFAULT 'contact_unlock'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('wallet_recharge', 'contact_unlock', 'payout', 'usage_fee') NOT NULL DEFAULT 'contact_unlock'");
    }
};
