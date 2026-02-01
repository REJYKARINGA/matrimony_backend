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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('account_name');
            $table->string('account_number');
            $table->string('ifsc_code');
            $table->string('razorpay_fund_account_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        // Remove columns from users table if they exist
        Schema::table('users', function (Blueprint $table) {
            $columns = ['bank_account_name', 'bank_account_number', 'bank_ifsc_code', 'razorpay_fund_account_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');

        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_ifsc_code')->nullable();
            $table->string('razorpay_fund_account_id')->nullable();
        });
    }
};
