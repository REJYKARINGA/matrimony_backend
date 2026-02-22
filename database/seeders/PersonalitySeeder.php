<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('personalities')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $personalities = [
            // MBTI Types
            ['personality_name' => 'INTJ - The Architect', 'personality_type' => 'MBTI', 'trending_number' => 1],
            ['personality_name' => 'INTP - The Logician', 'personality_type' => 'MBTI', 'trending_number' => 2],
            ['personality_name' => 'ENTJ - The Commander', 'personality_type' => 'MBTI', 'trending_number' => 3],
            ['personality_name' => 'ENTP - The Debater', 'personality_type' => 'MBTI', 'trending_number' => 4],
            ['personality_name' => 'INFJ - The Advocate', 'personality_type' => 'MBTI', 'trending_number' => 5],
            ['personality_name' => 'INFP - The Mediator', 'personality_type' => 'MBTI', 'trending_number' => 6],
            ['personality_name' => 'ENFJ - The Protagonist', 'personality_type' => 'MBTI', 'trending_number' => 7],
            ['personality_name' => 'ENFP - The Campaigner', 'personality_type' => 'MBTI', 'trending_number' => 8],
            ['personality_name' => 'ISTJ - The Logistician', 'personality_type' => 'MBTI', 'trending_number' => 9],
            ['personality_name' => 'ISFJ - The Defender', 'personality_type' => 'MBTI', 'trending_number' => 10],
            ['personality_name' => 'ESTJ - The Executive', 'personality_type' => 'MBTI', 'trending_number' => 11],
            ['personality_name' => 'ESFJ - The Consul', 'personality_type' => 'MBTI', 'trending_number' => 12],
            ['personality_name' => 'ISTP - The Virtuoso', 'personality_type' => 'MBTI', 'trending_number' => 13],
            ['personality_name' => 'ISFP - The Adventurer', 'personality_type' => 'MBTI', 'trending_number' => 14],
            ['personality_name' => 'ESTP - The Entrepreneur', 'personality_type' => 'MBTI', 'trending_number' => 15],
            ['personality_name' => 'ESFP - The Entertainer', 'personality_type' => 'MBTI', 'trending_number' => 16],

            // Big Five Traits
            ['personality_name' => 'Open', 'personality_type' => 'Big Five', 'trending_number' => 17],
            ['personality_name' => 'Conscientious', 'personality_type' => 'Big Five', 'trending_number' => 18],
            ['personality_name' => 'Extraverted', 'personality_type' => 'Big Five', 'trending_number' => 19],
            ['personality_name' => 'Agreeable', 'personality_type' => 'Big Five', 'trending_number' => 20],
            ['personality_name' => 'Neurotic', 'personality_type' => 'Big Five', 'trending_number' => 21],

            // Enneagram Types
            ['personality_name' => 'Type 1 - The Reformer', 'personality_type' => 'Enneagram', 'trending_number' => 22],
            ['personality_name' => 'Type 2 - The Helper', 'personality_type' => 'Enneagram', 'trending_number' => 23],
            ['personality_name' => 'Type 3 - The Achiever', 'personality_type' => 'Enneagram', 'trending_number' => 24],
            ['personality_name' => 'Type 4 - The Individualist', 'personality_type' => 'Enneagram', 'trending_number' => 25],
            ['personality_name' => 'Type 5 - The Investigator', 'personality_type' => 'Enneagram', 'trending_number' => 26],
            ['personality_name' => 'Type 6 - The Loyalist', 'personality_type' => 'Enneagram', 'trending_number' => 27],
            ['personality_name' => 'Type 7 - The Enthusiast', 'personality_type' => 'Enneagram', 'trending_number' => 28],
            ['personality_name' => 'Type 8 - The Challenger', 'personality_type' => 'Enneagram', 'trending_number' => 29],
            ['personality_name' => 'Type 9 - The Peacemaker', 'personality_type' => 'Enneagram', 'trending_number' => 30],
        ];

        foreach ($personalities as $personality) {
            DB::table('personalities')->insert([
                'personality_name' => $personality['personality_name'],
                'personality_type' => $personality['personality_type'],
                'trending_number' => $personality['trending_number'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
