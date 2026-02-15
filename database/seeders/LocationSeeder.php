<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserProfile;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $profiles = UserProfile::all();

        $this->command->info('Updating location coordinates for ' . $profiles->count() . ' profiles...');

        // Kerala approximate bounds
        // Latitude: 8.2 to 12.8
        // Longitude: 75.0 to 77.0

        foreach ($profiles as $profile) {
            // Generate random coordinates within Kerala/South India region
            $latitude = 8.2 + (mt_rand() / mt_getrandmax()) * (12.8 - 8.2);
            $longitude = 75.0 + (mt_rand() / mt_getrandmax()) * (77.0 - 75.0);

            $profile->latitude = $latitude;
            $profile->longitude = $longitude;
            $profile->location_updated_at = now();
            $profile->save();
        }

        $this->command->info('Successfully updated latitude and longitude for all user profiles.');
    }
}
