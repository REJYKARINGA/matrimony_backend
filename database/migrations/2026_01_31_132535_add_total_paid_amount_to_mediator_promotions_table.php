<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mediator_promotions', function (Blueprint $table) {
            $table->decimal('total_paid_amount', 10, 2)->default(0)->after('calculated_payout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mediator_promotions', function (Blueprint $table) {
            $table->dropColumn('total_paid_amount');
        });
    }
};
