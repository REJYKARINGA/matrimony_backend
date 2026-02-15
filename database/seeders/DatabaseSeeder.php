<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(EducationSeeder::class);
        $this->call(OccupationSeeder::class);
        $this->call(ReligionCasteSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(MatrimonySeeder::class);
        $this->call(MatrimonyIdSeeder::class);
        $this->call(LocationSeeder::class);
    }
}
