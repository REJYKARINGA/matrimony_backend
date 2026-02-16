<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

class MatchingDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Target user: rejy1@yopmail.com (usually a female in MatrimonySeeder)
        $targetUser = User::where('email', 'rejy1@yopmail.com')->first();

        if (!$targetUser) {
            $this->command->error('Target user rejy1@yopmail.com not found. Please run MatrimonySeeder first.');
            return;
        }

        // Get some other users (males)
        $males = User::whereHas('userProfile', function ($query) {
            $query->where('gender', 'male');
        })->take(10)->get();

        if ($males->count() < 5) {
            $this->command->error('Not enough male users found to seed matching data.');
            return;
        }

        $this->seedForUser($targetUser, $males);

        // Also seed for Admin user if they exist
        $adminUser = User::where('email', 'admin@matrimony.com')->first();
        if ($adminUser) {
            $this->seedForUser($adminUser, $males);
        }

        $this->command->info('Matching data seeded successfully!');
    }

    private function seedForUser($targetUser, $others)
    {
        // Filter out self from others
        $others = $others->filter(fn($u) => $u->id !== $targetUser->id)->values();

        // Ensure we have enough 'others' to seed data
        if ($others->count() < 5) {
            $this->command->warn("Not enough other users available to seed full matching data for user ID: {$targetUser->id}.");
            return;
        }

        // 1. Seed Sent Interest (Pending)
        DB::table('interests_sent')->updateOrInsert(
            ['sender_id' => $targetUser->id, 'receiver_id' => $others[0]->id],
            [
                'status' => 'pending',
                'message' => 'Hey, I really liked your profile!',
                'sent_at' => Carbon::now()->subDays(2),
            ]
        );

        // 2. Seed Received Interest (Pending)
        DB::table('interests_sent')->updateOrInsert(
            ['sender_id' => $others[1]->id, 'receiver_id' => $targetUser->id],
            [
                'status' => 'pending',
                'message' => 'Hello, would love to connect with you.',
                'sent_at' => Carbon::now()->subDays(1),
            ]
        );

        // 3. Seed Match
        DB::table('interests_sent')->updateOrInsert(
            ['sender_id' => $others[2]->id, 'receiver_id' => $targetUser->id],
            [
                'status' => 'accepted',
                'message' => 'Let\'s get to know each other.',
                'sent_at' => Carbon::now()->subDays(5),
                'responded_at' => Carbon::now()->subDays(4),
            ]
        );

        DB::table('matches')->updateOrInsert(
            [
                'user1_id' => min($targetUser->id, $others[2]->id),
                'user2_id' => max($targetUser->id, $others[2]->id)
            ],
            [
                'match_score' => 9.2,
                'status' => 'matched',
                'created_at' => Carbon::now()->subDays(4),
                'updated_at' => Carbon::now()->subDays(4),
            ]
        );

        // 4. Seed Declined (Sent by target user and rejected)
        DB::table('interests_sent')->updateOrInsert(
            ['sender_id' => $targetUser->id, 'receiver_id' => $others[3]->id],
            [
                'status' => 'rejected',
                'message' => 'I am interested in your profile.',
                'sent_at' => Carbon::now()->subDays(3),
                'responded_at' => Carbon::now()->subDays(2),
            ]
        );

        // 5. Seed Declined (Received by target user and rejected)
        DB::table('interests_sent')->updateOrInsert(
            ['sender_id' => $others[4]->id, 'receiver_id' => $targetUser->id],
            [
                'status' => 'rejected',
                'message' => 'Hi, can we talk?',
                'sent_at' => Carbon::now()->subDays(2),
                'responded_at' => Carbon::now()->subDays(1),
            ]
        );
    }
}
