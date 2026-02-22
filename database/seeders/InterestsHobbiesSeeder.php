<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InterestsHobbiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('interests_hobbies')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $interests = [
            // Sports
            ['interest_name' => 'Cricket', 'interest_type' => 'sports', 'trending_number' => 5],
            ['interest_name' => 'Football', 'interest_type' => 'sports', 'trending_number' => 3],
            ['interest_name' => 'Basketball', 'interest_type' => 'sports', 'trending_number' => 8],
            ['interest_name' => 'Tennis', 'interest_type' => 'sports', 'trending_number' => 12],
            ['interest_name' => 'Badminton', 'interest_type' => 'sports', 'trending_number' => 10],
            ['interest_name' => 'Volleyball', 'interest_type' => 'sports', 'trending_number' => 15],
            ['interest_name' => 'Table Tennis', 'interest_type' => 'sports', 'trending_number' => 18],
            ['interest_name' => 'Swimming', 'interest_type' => 'sports', 'trending_number' => 7],
            ['interest_name' => 'Cycling', 'interest_type' => 'sports', 'trending_number' => 14],
            ['interest_name' => 'Yoga', 'interest_type' => 'sports', 'trending_number' => 4],

            // Arts
            ['interest_name' => 'Painting', 'interest_type' => 'arts', 'trending_number' => 22],
            ['interest_name' => 'Drawing', 'interest_type' => 'arts', 'trending_number' => 25],
            ['interest_name' => 'Photography', 'interest_type' => 'arts', 'trending_number' => 6],
            ['interest_name' => 'Dance', 'interest_type' => 'arts', 'trending_number' => 9],
            ['interest_name' => 'Sculpting', 'interest_type' => 'arts', 'trending_number' => 35],
            ['interest_name' => 'Crafts', 'interest_type' => 'arts', 'trending_number' => 28],

            // Music
            ['interest_name' => 'Singing', 'interest_type' => 'music', 'trending_number' => 4],
            ['interest_name' => 'Guitar', 'interest_type' => 'music', 'trending_number' => 11],
            ['interest_name' => 'Piano', 'interest_type' => 'music', 'trending_number' => 16],
            ['interest_name' => 'Violin', 'interest_type' => 'music', 'trending_number' => 30],
            ['interest_name' => 'Drums', 'interest_type' => 'music', 'trending_number' => 24],
            ['interest_name' => 'Music Composition', 'interest_type' => 'music', 'trending_number' => 32],

            // Reading
            ['interest_name' => 'Fiction', 'interest_type' => 'reading', 'trending_number' => 13],
            ['interest_name' => 'Non-Fiction', 'interest_type' => 'reading', 'trending_number' => 17],
            ['interest_name' => 'Poetry', 'interest_type' => 'reading', 'trending_number' => 40],
            ['interest_name' => 'Self-Help', 'interest_type' => 'reading', 'trending_number' => 19],
            ['interest_name' => 'Biographies', 'interest_type' => 'reading', 'trending_number' => 26],

            // Outdoor
            ['interest_name' => 'Hiking', 'interest_type' => 'outdoor', 'trending_number' => 21],
            ['interest_name' => 'Camping', 'interest_type' => 'outdoor', 'trending_number' => 23],
            ['interest_name' => 'Gardening', 'interest_type' => 'outdoor', 'trending_number' => 27],
            ['interest_name' => 'Bird Watching', 'interest_type' => 'outdoor', 'trending_number' => 45],
            ['interest_name' => 'Fishing', 'interest_type' => 'outdoor', 'trending_number' => 33],

            // Culinary
            ['interest_name' => 'Cooking', 'interest_type' => 'culinary', 'trending_number' => 2],
            ['interest_name' => 'Baking', 'interest_type' => 'culinary', 'trending_number' => 15],
            ['interest_name' => 'Wine Tasting', 'interest_type' => 'culinary', 'trending_number' => 38],
            ['interest_name' => 'Coffee Brewing', 'interest_type' => 'culinary', 'trending_number' => 29],

            // Travel
            ['interest_name' => 'Traveling', 'interest_type' => 'travel', 'trending_number' => 1],
            ['interest_name' => 'Backpacking', 'interest_type' => 'travel', 'trending_number' => 31],
            ['interest_name' => 'Road Trips', 'interest_type' => 'travel', 'trending_number' => 24],
            ['interest_name' => 'Cultural Exploration', 'interest_type' => 'travel', 'trending_number' => 34],

            // Fitness
            ['interest_name' => 'Meditation', 'interest_type' => 'fitness', 'trending_number' => 8],
            ['interest_name' => 'Gym Workout', 'interest_type' => 'fitness', 'trending_number' => 6],
            ['interest_name' => 'Running', 'interest_type' => 'fitness', 'trending_number' => 11],
            ['interest_name' => 'CrossFit', 'interest_type' => 'fitness', 'trending_number' => 36],
            ['interest_name' => 'Martial Arts', 'interest_type' => 'fitness', 'trending_number' => 28],

            // Social
            ['interest_name' => 'Volunteering', 'interest_type' => 'social', 'trending_number' => 37],
            ['interest_name' => 'Public Speaking', 'interest_type' => 'social', 'trending_number' => 42],
            ['interest_name' => 'Board Games', 'interest_type' => 'social', 'trending_number' => 39],
            ['interest_name' => 'Chess', 'interest_type' => 'social', 'trending_number' => 17],
            ['interest_name' => 'Networking', 'interest_type' => 'social', 'trending_number' => 44],

            // Technology
            ['interest_name' => 'Gaming', 'interest_type' => 'technology', 'trending_number' => 2],
            ['interest_name' => 'Coding', 'interest_type' => 'technology', 'trending_number' => 10],
            ['interest_name' => 'AI & Machine Learning', 'interest_type' => 'technology', 'trending_number' => 5],
            ['interest_name' => 'Robotics', 'interest_type' => 'technology', 'trending_number' => 33],
            ['interest_name' => '3D Printing', 'interest_type' => 'technology', 'trending_number' => 41],

            // Entertainment
            ['interest_name' => 'Movies', 'interest_type' => 'entertainment', 'trending_number' => 3],
            ['interest_name' => 'TV Series', 'interest_type' => 'entertainment', 'trending_number' => 7],
            ['interest_name' => 'Streaming', 'interest_type' => 'entertainment', 'trending_number' => 9],
            ['interest_name' => 'Theater', 'interest_type' => 'entertainment', 'trending_number' => 35],
            ['interest_name' => 'Stand-up Comedy', 'interest_type' => 'entertainment', 'trending_number' => 26],

            // Lifestyle
            ['interest_name' => 'Fashion', 'interest_type' => 'lifestyle', 'trending_number' => 12],
            ['interest_name' => 'Beauty & Makeup', 'interest_type' => 'lifestyle', 'trending_number' => 14],
            ['interest_name' => 'Interior Design', 'interest_type' => 'lifestyle', 'trending_number' => 20],
            ['interest_name' => 'Minimalism', 'interest_type' => 'lifestyle', 'trending_number' => 30],
            ['interest_name' => 'Sustainable Living', 'interest_type' => 'lifestyle', 'trending_number' => 25],
        ];

        foreach ($interests as $interest) {
            DB::table('interests_hobbies')->insert([
                'interest_name' => $interest['interest_name'],
                'interest_type' => $interest['interest_type'],
                'trending_number' => $interest['trending_number'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
