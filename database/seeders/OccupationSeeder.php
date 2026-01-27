<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OccupationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('occupations')->truncate();

        $occupations = [
            ['name' => 'Software Engineer', 'is_active' => true, 'order_number' => 1],
            ['name' => 'Doctor', 'is_active' => true, 'order_number' => 2],
            ['name' => 'Business Owner', 'is_active' => true, 'order_number' => 3],
            ['name' => 'Teacher', 'is_active' => true, 'order_number' => 4],
            ['name' => 'Engineer', 'is_active' => true, 'order_number' => 5],
            ['name' => 'Chartered Accountant', 'is_active' => true, 'order_number' => 6],
            ['name' => 'Lawyer', 'is_active' => true, 'order_number' => 7],
            ['name' => 'Banker', 'is_active' => true, 'order_number' => 8],
            ['name' => 'Government Employee', 'is_active' => true, 'order_number' => 9],
            ['name' => 'Architect', 'is_active' => true, 'order_number' => 10],
            ['name' => 'Consultant', 'is_active' => true, 'order_number' => 11],
            ['name' => 'Professor', 'is_active' => true, 'order_number' => 12],
            ['name' => 'Pharmacist', 'is_active' => true, 'order_number' => 13],
            ['name' => 'Nurse', 'is_active' => true, 'order_number' => 14],
            ['name' => 'Civil Servant', 'is_active' => true, 'order_number' => 15],
            ['name' => 'Marketing Professional', 'is_active' => true, 'order_number' => 16],
            ['name' => 'Sales Professional', 'is_active' => true, 'order_number' => 17],
            ['name' => 'HR Professional', 'is_active' => true, 'order_number' => 18],
            ['name' => 'Finance Professional', 'is_active' => true, 'order_number' => 19],
            ['name' => 'Data Scientist', 'is_active' => true, 'order_number' => 20],
            ['name' => 'Designer', 'is_active' => true, 'order_number' => 21],
            ['name' => 'Pilot', 'is_active' => true, 'order_number' => 22],
            ['name' => 'Defense Services', 'is_active' => true, 'order_number' => 23],
            ['name' => 'Police Officer', 'is_active' => true, 'order_number' => 24],
            ['name' => 'Entrepreneur', 'is_active' => true, 'order_number' => 25],
            ['name' => 'Scientist', 'is_active' => true, 'order_number' => 26],
            ['name' => 'Researcher', 'is_active' => true, 'order_number' => 27],
            ['name' => 'Journalist', 'is_active' => true, 'order_number' => 28],
            ['name' => 'Artist', 'is_active' => true, 'order_number' => 29],
            ['name' => 'Chef', 'is_active' => true, 'order_number' => 30],
            ['name' => 'Hospitality Professional', 'is_active' => true, 'order_number' => 31],
            ['name' => 'Real Estate Professional', 'is_active' => true, 'order_number' => 32],
            ['name' => 'Agriculturist', 'is_active' => true, 'order_number' => 33],
            ['name' => 'Student', 'is_active' => true, 'order_number' => 34],
            ['name' => 'Homemaker', 'is_active' => true, 'order_number' => 35],
            ['name' => 'Not Working', 'is_active' => true, 'order_number' => 36],
            ['name' => 'Other', 'is_active' => true, 'order_number' => 37],
        ];

        foreach ($occupations as $occupation) {
            \DB::table('occupations')->insert([
                'name' => $occupation['name'],
                'is_active' => $occupation['is_active'],
                'order_number' => $occupation['order_number'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
