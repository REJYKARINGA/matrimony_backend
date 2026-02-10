<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UserProfile;

class UserProfileLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Central point: Malappuram, Kerala (approx)
        $centerLat = 11.050972;
        $centerLon = 76.071098;

        $profiles = UserProfile::whereNull('latitude')->orWhereNull('longitude')->get();

        $this->command->info("Updating " . $profiles->count() . " profile locations...");

        foreach ($profiles as $profile) {
            // Generate random coordinates within ~100km radius
            // rough approximation: 1 degree approx 111km
            $latOffset = (mt_rand(-1000, 1000) / 1000); // approx +/- 111km
            $lonOffset = (mt_rand(-1000, 1000) / 1000); // approx +/- 111km

            $profile->update([
                'latitude' => $centerLat + $latOffset,
                'longitude' => $centerLon + $lonOffset,
                'location_updated_at' => now(),
            ]);
        }

        $this->command->info("Locations updated successfully.");
    }
}
