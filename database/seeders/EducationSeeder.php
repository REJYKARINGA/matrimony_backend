<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EducationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('education')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $educations = [
            ['name' => 'Doctorate/PhD', 'is_active' => true, 'order_number' => 1],
            ['name' => 'Post Graduate', 'is_active' => true, 'order_number' => 2],
            ['name' => 'Graduate', 'is_active' => true, 'order_number' => 3],
            ['name' => 'Undergraduate', 'is_active' => true, 'order_number' => 4],
            ['name' => 'Diploma', 'is_active' => true, 'order_number' => 5],
            ['name' => 'High School', 'is_active' => true, 'order_number' => 6],
            ['name' => 'MBA', 'is_active' => true, 'order_number' => 7],
            ['name' => 'MCA', 'is_active' => true, 'order_number' => 8],
            ['name' => 'M.Tech', 'is_active' => true, 'order_number' => 9],
            ['name' => 'M.Sc', 'is_active' => true, 'order_number' => 10],
            ['name' => 'M.A', 'is_active' => true, 'order_number' => 11],
            ['name' => 'M.Com', 'is_active' => true, 'order_number' => 12],
            ['name' => 'B.Tech/B.E', 'is_active' => true, 'order_number' => 13],
            ['name' => 'BCA', 'is_active' => true, 'order_number' => 14],
            ['name' => 'B.Sc', 'is_active' => true, 'order_number' => 15],
            ['name' => 'B.A', 'is_active' => true, 'order_number' => 16],
            ['name' => 'B.Com', 'is_active' => true, 'order_number' => 17],
            ['name' => 'BBA', 'is_active' => true, 'order_number' => 18],
            ['name' => 'MBBS', 'is_active' => true, 'order_number' => 19],
            ['name' => 'MD/MS', 'is_active' => true, 'order_number' => 20],
            ['name' => 'BDS', 'is_active' => true, 'order_number' => 21],
            ['name' => 'MDS', 'is_active' => true, 'order_number' => 22],
            ['name' => 'BAMS', 'is_active' => true, 'order_number' => 23],
            ['name' => 'BHMS', 'is_active' => true, 'order_number' => 24],
            ['name' => 'B.Pharm', 'is_active' => true, 'order_number' => 25],
            ['name' => 'M.Pharm', 'is_active' => true, 'order_number' => 26],
            ['name' => 'LLB', 'is_active' => true, 'order_number' => 27],
            ['name' => 'LLM', 'is_active' => true, 'order_number' => 28],
            ['name' => 'CA', 'is_active' => true, 'order_number' => 29],
            ['name' => 'CS', 'is_active' => true, 'order_number' => 30],
            ['name' => 'ICWA', 'is_active' => true, 'order_number' => 31],
            ['name' => 'B.Arch', 'is_active' => true, 'order_number' => 32],
            ['name' => 'M.Arch', 'is_active' => true, 'order_number' => 33],
            ['name' => 'B.Ed', 'is_active' => true, 'order_number' => 34],
            ['name' => 'M.Ed', 'is_active' => true, 'order_number' => 35],
            ['name' => 'ITI', 'is_active' => true, 'order_number' => 36],
            ['name' => 'Polytechnic', 'is_active' => true, 'order_number' => 37],
            ['name' => 'Other', 'is_active' => true, 'order_number' => 38],
        ];

        foreach ($educations as $education) {
            \DB::table('education')->insert([
                'name' => $education['name'],
                'is_active' => $education['is_active'],
                'order_number' => $education['order_number'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
