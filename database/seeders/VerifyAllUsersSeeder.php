<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserProfile;

class VerifyAllUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserProfile::query()->update(['is_active_verified' => true]);
        $this->command->info('All user profiles have been verified (is_active_verified = true)!');
    }
}
