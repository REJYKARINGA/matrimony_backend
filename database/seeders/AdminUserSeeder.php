<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $admin = User::where('email', 'admin@matrimony.com')->first();

        if (!$admin) {
            // Create admin user if it doesn't exist
            $admin = User::create([
                'email' => 'admin@matrimony.com',
                'phone' => '+1234567890',
                'password' => Hash::make('admin123'), // Hashed password
                'role' => 'admin',
                'status' => 'active',
                'email_verified' => true,
            ]);

            $religionId = \DB::table('religions')->where('name', 'Hindu')->value('id') ?? \DB::table('religions')->first()?->id;
            $educationId = \DB::table('education')->where('name', 'Masters')->value('id') ?? \DB::table('education')->first()?->id;
            $occupationId = \DB::table('occupations')->where('name', 'Software Engineer')->value('id') ?? \DB::table('occupations')->first()?->id;

            // Create a sample user profile for the admin
            \App\Models\UserProfile::create([
                'user_id' => $admin->id,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'date_of_birth' => '1990-01-01',
                'gender' => 'male',
                'height' => 175,
                'weight' => 70,
                'marital_status' => 'never_married',
                'religion_id' => $religionId,
                'mother_tongue' => 'English',
                'bio' => 'System Administrator',
                'education_id' => $educationId,
                'occupation_id' => $occupationId,
                'annual_income' => 1000000,
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'drug_addiction' => false,
                'smoke' => 'never',
                'alcohol' => 'never',
            ]);
        } else {
            $this->command->info('Admin user already exists. Skipping creation.');
        }

        // Check if subscription plans already exist to avoid duplicates
        $existingPlans = \Illuminate\Support\Facades\DB::table('subscription_plans')
            ->where('name', 'LIKE', '%Plan')
            ->count();

        if ($existingPlans === 0) {
            // Create sample subscription plans for testing
            \Illuminate\Support\Facades\DB::table('subscription_plans')->insert([
                'name' => 'Premium Plan',
                'duration_days' => 30,
                'price' => 999.00,
                'max_messages' => 100,
                'max_contacts' => 50,
                'can_view_contact' => 1,
                'priority_listing' => 1,
                'features' => json_encode(['Featured Profile', 'Priority Support', 'Advanced Search']),
                'is_active' => 1,
                'created_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::table('subscription_plans')->insert([
                'name' => 'Basic Plan',
                'duration_days' => 15,
                'price' => 499.00,
                'max_messages' => 25,
                'max_contacts' => 10,
                'can_view_contact' => 0,
                'priority_listing' => 0,
                'features' => json_encode(['Standard Profile', 'Basic Search']),
                'is_active' => 1,
                'created_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::table('subscription_plans')->insert([
                'name' => 'Free Plan',
                'duration_days' => 7,
                'price' => 0.00,
                'max_messages' => 5,
                'max_contacts' => 3,
                'can_view_contact' => 0,
                'priority_listing' => 0,
                'features' => json_encode(['Basic Profile']),
                'is_active' => 1,
                'created_at' => now(),
            ]);
        } else {
            $this->command->info('Subscription plans already exist. Skipping creation.');
        }

        $this->command->info('Seeding completed successfully!');
        if (!$admin) {
            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: admin@matrimony.com');
            $this->command->info('Password: admin123');
        }
    }
}
