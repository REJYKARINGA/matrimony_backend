<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class MatrimonyIdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $prefix = 'VE';

        $this->command->info('Updating Matrimony IDs for ' . $users->count() . ' users...');

        foreach ($users as $user) {
            $unique = false;
            $newId = '';

            // Try to generate a unique ID
            while (!$unique) {
                // Generate 7 random digits
                // rand(1000000, 9999999) ensures exactly 7 digits
                $number = rand(1000000, 9999999);
                $newId = $prefix . $number;

                // Check if this ID already exists in the database
                // distinct from the current user in case of collision with another user's existing ID
                // (though with a new prefix 'VE' vs existing 'VM', collision is only with already processed users in this loop)
                if (!User::where('matrimony_id', $newId)->where('id', '!=', $user->id)->exists()) {
                    $unique = true;
                }
            }

            $user->matrimony_id = $newId;
            $user->save();
        }

        $this->command->info('Successfully updated Matrimony IDs to VE format (VE + 7 digits).');
    }
}
