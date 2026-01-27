<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\FamilyDetail;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 11; $i <= 60; $i++) {
            $email = "rejy{$i}@yopmail.com";

            // Check if user already exists
            $user = User::where('email', $email)->first();

            if (!$user) {
                $gender = $faker->randomElement(['male', 'female']);
                $firstName = $faker->firstName($gender);
                $lastName = $faker->lastName;

                // Create user
                $user = User::create([
                    'email' => $email,
                    'phone' => $faker->phoneNumber,
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'status' => 'active',
                    'email_verified' => true,
                    'phone_verified' => true,
                ]);

                // Create user profile
                UserProfile::create([
                    'user_id' => $user->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $faker->dateTimeBetween('-50 years', '-18 years'),
                    'gender' => $gender,
                    'height' => $faker->numberBetween(150, 200),
                    'weight' => $faker->numberBetween(50, 100),
                    'marital_status' => $faker->randomElement(['never_married', 'divorced', 'widowed']),
                    'religion' => $faker->randomElement(['Hindu', 'Muslim', 'Christian', 'Sikh', 'Buddhist']),
                    'caste' => $faker->word,
                    'mother_tongue' => $faker->randomElement(['Hindi', 'English', 'Tamil', 'Telugu', 'Bengali']),
                    'bio' => $faker->paragraph,
                    'education' => $faker->randomElement(['Bachelors', 'Masters', 'PhD', 'Diploma']),
                    'occupation' => $faker->jobTitle,
                    'annual_income' => $faker->numberBetween(100000, 5000000),
                    'city' => $faker->city,
                    'district' => $faker->randomElement(['Thiruvananthapuram', 'Kollam', 'Pathanamthitta', 'Alappuzha', 'Kottayam', 'Idukki', 'Ernakulam', 'Thrissur', 'Palakkad', 'Malappuram', 'Kozhikode', 'Wayanad', 'Kannur', 'Kasaragod']),
                    'state' => 'Kerala',
                    'country' => 'India',
                    'is_active_verified' => true,
                ]);

                // Create family details
                FamilyDetail::create([
                    'user_id' => $user->id,
                    'father_name' => $faker->name('male'),
                    'father_occupation' => $faker->jobTitle,
                    'mother_name' => $faker->name('female'),
                    'mother_occupation' => $faker->jobTitle,
                    'siblings' => $faker->numberBetween(0, 5),
                    'family_type' => $faker->randomElement(['joint', 'nuclear']),
                    'family_status' => $faker->randomElement(['middle_class', 'upper_middle_class', 'rich']),
                    'family_location' => $faker->city,
                    'elder_sister' => $faker->numberBetween(0, 3),
                    'elder_brother' => $faker->numberBetween(0, 3),
                    'younger_sister' => $faker->numberBetween(0, 3),
                    'younger_brother' => $faker->numberBetween(0, 3),
                    'twin_type' => $faker->randomElement(['identical', 'fraternal']),
                    'father_alive' => $faker->boolean,
                    'mother_alive' => $faker->boolean,
                    'guardian' => $faker->name,
                    'show' => $faker->boolean,
                ]);

                $this->command->info("Created user: {$email}");
            } else {
                $this->command->info("User {$email} already exists. Skipping.");
            }
        }
    }
}
