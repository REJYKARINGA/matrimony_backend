<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MatrimonySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear existing data
        DB::table('profile_photos')->delete();
        DB::table('interests_sent')->delete();
        DB::table('matches')->delete();
        DB::table('messages')->delete();
        DB::table('profile_views')->delete();
        DB::table('shortlisted_profiles')->delete();
        DB::table('user_subscriptions')->delete();
        DB::table('payments')->delete();
        DB::table('activity_logs')->delete();
        DB::table('reports')->delete();
        DB::table('notifications')->delete();
        DB::table('preferences')->delete();
        DB::table('family_details')->delete();
        DB::table('user_profiles')->delete();
        DB::table('users')->delete();

        // Define 30 female users (Muslim Kerala)
        $females = [
            ['Fatimah', 'Zahra', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Software Engineer', 'B.Tech CS', 850000.00, 'Kozhikode', 'Kerala'],
            ['Aisha', 'Hana', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Doctor', 'MBBS', 1200000.00, 'Malappuram', 'Kerala'],
            ['Maryam', 'Nadiya', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Professor', 'PhD Literature', 750000.00, 'Kochi', 'Kerala'],
            ['Khadija', 'Safiya', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Nurse', 'B.Sc Nursing', 550000.00, 'Thalassery', 'Kerala'],
            ['Zainab', 'Rehana', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Architect', 'B.Arch', 900000.00, 'Thiruvananthapuram', 'Kerala'],
            ['Hana', 'Sumaiya', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Chartered Accountant', 'CA', 1100000.00, 'Thrissur', 'Kerala'],
            ['Amina', 'Naseema', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'School Teacher', 'B.Ed', 450000.00, 'Palakkad', 'Kerala'],
            ['Shamna', 'Shadiya', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Graphic Designer', 'BFA', 600000.00, 'Kannur', 'Kerala'],
            ['Fida', 'Liya', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Psychologist', 'M.Sc Psychology', 650000.00, 'Alappuzha', 'Kerala'],
            ['Sana', 'Meher', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Civil Engineer', 'M.Tech Civil', 800000.00, 'Kasaragod', 'Kerala'],
            ['Arshida', 'Jasna', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Pharmacist', 'B.Pharm', 500000.00, 'Manjeri', 'Kerala'],
            ['Riswana', 'Shabana', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Data Scientist', 'M.Sc Data Science', 950000.00, 'Kochi', 'Kerala'],
            ['Sajna', 'Thasni', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'HR Manager', 'MBA HR', 720000.00, 'Perintalmanna', 'Kerala'],
            ['Asna', 'Bushra', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Physiotherapist', 'BPT', 580000.00, 'Kollam', 'Kerala'],
            ['Fahima', 'Irfana', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Content Writer', 'MA English', 420000.00, 'Wayanad', 'Kerala'],
            ['Shahana', 'Zahra', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Interior Designer', 'B.Des', 680000.00, 'Kozhikode', 'Kerala'],
            ['Nadiya', 'Fathima', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Advocate', 'LLB', 700000.00, 'Kottayam', 'Kerala'],
            ['Rubeena', 'Parveen', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Dentist', 'BDS', 880000.00, 'Vatagara', 'Kerala'],
            ['Suhara', 'Banu', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Journalist', 'MCJ', 520000.00, 'Thiruvananthapuram', 'Kerala'],
            ['Maimuna', 'Koya', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Homemaker', 'BA', 0.00, 'Kozhikode', 'Kerala'],
            ['Aysha', 'Siddeeqa', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Dietician', 'M.Sc Nutrition', 480000.00, 'Malappuram', 'Kerala'],
            ['Jamshiya', 'K.P.', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Civil Servant', 'IAS', 1500000.00, 'Tirur', 'Kerala'],
            ['Nishana', 'V.P.', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Bank Manager', 'MBA Finance', 920000.00, 'Kochi', 'Kerala'],
            ['Shameema', 'M.T.', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Yoga Trainer', 'Certification', 350000.00, 'Ponnani', 'Kerala'],
            ['Lulu', 'Marjan', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Researcher', 'PhD Biotech', 820000.00, 'Thalassery', 'Kerala'],
            ['Hiba', 'Fathima', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Fashion Designer', 'Diploma', 400000.00, 'Kannur', 'Kerala'],
            ['Minha', 'Sherin', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Web Developer', 'BCA', 620000.00, 'Kozhikode', 'Kerala'],
            ['Roshna', 'Naseer', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Marketing Executive', 'MBA', 550000.00, 'Kochi', 'Kerala'],
            ['Sumayya', 'Sherin', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Teacher', 'TTC', 300000.00, 'Malappuram', 'Kerala'],
            ['Farzana', 'Iqbal', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Entrepreneur', 'B.Com', 1200000.00, 'Kozhikode', 'Kerala'],
        ];

        // Define 20 male users (Muslim Kerala)
        $males = [
            ['Muhammed', 'Ali', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Software Architect', 'M.Tech', 1800000.00, 'Kozhikode', 'Kerala'],
            ['Ahmed', 'Faisal', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Business Owner', 'MBA', 2500000.00, 'Malappuram', 'Kerala'],
            ['Hassan', 'Rashid', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Civil Engineer', 'B.Tech', 950000.00, 'Kochi', 'Kerala'],
            ['Hussain', 'Sameer', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Medical Doctor', 'MD', 2200000.00, 'Thiruvananthapuram', 'Kerala'],
            ['Umar', 'Shafi', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Accountant', 'CA', 1200000.00, 'Thrissur', 'Kerala'],
            ['Usman', 'Mansoor', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Government Employee', 'Degree', 700000.00, 'Kannur', 'Kerala'],
            ['Khalid', 'Saleem', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Chef', 'Hotel Management', 650000.00, 'Palakkad', 'Kerala'],
            ['Bilal', 'Anwar', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Marketing Manager', 'MBA', 1100000.00, 'Kozhikode', 'Kerala'],
            ['Faisal', 'Ashraf', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Police Officer', 'SI', 850000.00, 'Malappuram', 'Kerala'],
            ['Rashid', 'Noufal', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Network Engineer', 'MCSE', 780000.00, 'Kochi', 'Kerala'],
            ['Sameer', 'Jaseel', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Expatriate', 'Work Visa', 1500000.00, 'Tirur', 'Kerala'],
            ['Mansoor', 'Irshad', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Graphic Artist', 'Multimedia', 550000.00, 'Vatagara', 'Kerala'],
            ['Saleem', 'Shihab', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Pharmacist', 'B.Pharm', 620000.00, 'Perintalmanna', 'Kerala'],
            ['Anwar', 'Zakir', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Real Estate', 'Business', 2000000.00, 'Kozhikode', 'Kerala'],
            ['Ashraf', 'Yaseen', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Professor', 'PhD', 1300000.00, 'Kochi', 'Kerala'],
            ['Noufal', 'Nizam', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Web Designer', 'Degree', 580000.00, 'Kannur', 'Kerala'],
            ['Jaseel', 'Rafeeq', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Electrician', 'ITI', 450000.00, 'Kasaragod', 'Kerala'],
            ['Irshad', 'Shareef', 'Muslim', 'Mapila', 'Mujahid', 'Malayalam', 'Advocate', 'LLB', 900000.00, 'Manjeri', 'Kerala'],
            ['Shihab', 'Majeed', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Sales Manager', 'Degree', 720000.00, 'Alappuzha', 'Kerala'],
            ['Zakir', 'Haris', 'Muslim', 'Mapila', 'Sunni', 'Malayalam', 'Artist', 'BFA', 400000.00, 'Kochi', 'Kerala'],
        ];

        // Create/Update female users
        foreach ($females as $index => $female) {
            $email = 'rejy' . ($index + 1) . '@yopmail.com';

            $user = DB::table('users')->where('email', $email)->first();
            
            $userData = [
                'email' => $email,
                'phone' => '+9198765432' . str_pad($index + 10, 2, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'email_verified' => true,
                'phone_verified' => true,
                'updated_at' => Carbon::now(),
            ];

            if ($user) {
                $userId = $user->id;
                DB::table('users')->where('id', $userId)->update($userData);
            } else {
                $userData['created_at'] = Carbon::now();
                $userId = DB::table('users')->insertGetId($userData);
            }

            // Resolve IDs
            $religionId = DB::table('religions')->where('name', $female[2])->value('id') ?? 
                         DB::table('religions')->insertGetId(['name' => $female[2], 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $casteId = DB::table('castes')->where('name', $female[3])->where('religion_id', $religionId)->value('id') ?? 
                      DB::table('castes')->insertGetId(['name' => $female[3], 'religion_id' => $religionId, 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $subCasteId = DB::table('sub_castes')->where('name', $female[4])->where('caste_id', $casteId)->value('id') ?? 
                         DB::table('sub_castes')->insertGetId(['name' => $female[4], 'caste_id' => $casteId, 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $educationId = DB::table('education')->where('name', $female[7])->value('id') ?? 
                          DB::table('education')->insertGetId(['name' => $female[7], 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $occupationId = DB::table('occupations')->where('name', $female[6])->value('id') ?? 
                           DB::table('occupations')->insertGetId(['name' => $female[6], 'is_active' => true, 'created_at' => Carbon::now()]);

            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'first_name' => $female[0],
                    'last_name' => $female[1],
                    'date_of_birth' => Carbon::now()->subYears(rand(25, 35))->toDateString(),
                    'gender' => 'female',
                    'height' => rand(155, 168),
                    'weight' => rand(48, 62),
                    'marital_status' => 'never_married',
                    'religion_id' => $religionId,
                    'caste_id' => $casteId,
                    'sub_caste_id' => $subCasteId,
                    'mother_tongue' => $female[5],
                    'profile_picture' => 'https://example.com/profiles/' . strtolower($female[0]) . '.jpg',
                    'bio' => $female[0] . ' is a ' . $female[6] . ' with a passion for her work.',
                    'education_id' => $educationId,
                    'occupation_id' => $occupationId,
                    'annual_income' => $female[8],
                    'city' => $female[9],
                    'state' => $female[10],
                    'country' => 'India',
                    'updated_at' => Carbon::now(),
                ]
            );

            DB::table('family_details')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'father_name' => $this->generateFatherName($female[1]),
                    'father_occupation' => $this->getRandomOccupation(),
                    'mother_name' => $this->generateMotherName($female[1]),
                    'mother_occupation' => $this->getRandomFemaleOccupation(),
                    'siblings' => rand(0, 3),
                    'family_type' => rand(0, 1) ? 'nuclear' : 'joint',
                    'family_status' => $this->getRandomFamilyStatus(),
                    'family_location' => $female[9],
                    'updated_at' => Carbon::now(),
                ]
            );

            DB::table('preferences')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'min_age' => 25,
                    'max_age' => 35,
                    'min_height' => 170,
                    'max_height' => 188,
                    'marital_status' => 'never_married',
                    'religion_id' => $religionId,
                    'caste_ids' => json_encode([$casteId]),
                    'education_ids' => json_encode([$educationId]),
                    'occupation_ids' => json_encode([$occupationId]),
                    'min_income' => 500000.00,
                    'max_income' => 1500000.00,
                    'preferred_locations' => json_encode([$female[9], $this->getRandomCity(), $this->getRandomCity()]),
                    'drug_addiction' => 'any',
                    'smoke' => json_encode(['never']),
                    'alcohol' => json_encode(['never']),
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        // Create/Update male users
        foreach ($males as $index => $male) {
            $email = 'rejy' . ($index + 31) . '@yopmail.com'; // Males start from rejy31

            $user = DB::table('users')->where('email', $email)->first();
            
            $userData = [
                'email' => $email,
                'phone' => '+9198765432' . str_pad($index + 40, 2, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'email_verified' => true,
                'phone_verified' => true,
                'updated_at' => Carbon::now(),
            ];

            if ($user) {
                $userId = $user->id;
                DB::table('users')->where('id', $userId)->update($userData);
            } else {
                $userData['created_at'] = Carbon::now();
                $userId = DB::table('users')->insertGetId($userData);
            }

            // Resolve IDs
            $religionId = DB::table('religions')->where('name', $male[2])->value('id') ?? 
                         DB::table('religions')->insertGetId(['name' => $male[2], 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $casteId = DB::table('castes')->where('name', $male[3])->where('religion_id', $religionId)->value('id') ?? 
                      DB::table('castes')->insertGetId(['name' => $male[3], 'religion_id' => $religionId, 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $subCasteId = DB::table('sub_castes')->where('name', $male[4])->where('caste_id', $casteId)->value('id') ?? 
                         DB::table('sub_castes')->insertGetId(['name' => $male[4], 'caste_id' => $casteId, 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $educationId = DB::table('education')->where('name', $male[7])->value('id') ?? 
                          DB::table('education')->insertGetId(['name' => $male[7], 'is_active' => true, 'created_at' => Carbon::now()]);
            
            $occupationId = DB::table('occupations')->where('name', $male[6])->value('id') ?? 
                           DB::table('occupations')->insertGetId(['name' => $male[6], 'is_active' => true, 'created_at' => Carbon::now()]);

            DB::table('user_profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'first_name' => $male[0],
                    'last_name' => $male[1],
                    'date_of_birth' => Carbon::now()->subYears(rand(25, 35))->toDateString(),
                    'gender' => 'male',
                    'height' => rand(170, 185),
                    'weight' => rand(65, 80),
                    'marital_status' => 'never_married',
                    'religion_id' => $religionId,
                    'caste_id' => $casteId,
                    'sub_caste_id' => $subCasteId,
                    'mother_tongue' => $male[5],
                    'profile_picture' => 'https://example.com/profiles/' . strtolower($male[0]) . '.jpg',
                    'bio' => $male[0] . ' is a ' . $male[6] . ' with a passion for his work.',
                    'education_id' => $educationId,
                    'occupation_id' => $occupationId,
                    'annual_income' => $male[8],
                    'city' => $male[9],
                    'state' => $male[10],
                    'country' => 'India',
                    'is_active_verified' => 1,
                    'updated_at' => Carbon::now(),
                ]
            );

            DB::table('family_details')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'father_name' => $this->generateFatherName($male[1]),
                    'father_occupation' => $this->getRandomMaleOccupation(),
                    'mother_name' => $this->generateMotherName($male[1]),
                    'mother_occupation' => $this->getRandomFemaleOccupation(),
                    'siblings' => rand(0, 3),
                    'family_type' => rand(0, 1) ? 'nuclear' : 'joint',
                    'family_status' => $this->getRandomFamilyStatus(),
                    'family_location' => $male[9],
                    'updated_at' => Carbon::now(),
                ]
            );

            DB::table('preferences')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'min_age' => 22,
                    'max_age' => 32,
                    'min_height' => 155,
                    'max_height' => 170,
                    'marital_status' => 'never_married',
                    'religion_id' => $religionId,
                    'caste_ids' => json_encode([$casteId]),
                    'education_ids' => json_encode([$educationId]),
                    'occupation_ids' => json_encode([$occupationId]),
                    'min_income' => 400000.00,
                    'max_income' => 1200000.00,
                    'preferred_locations' => json_encode([$male[9], $this->getRandomCity(), $this->getRandomCity()]),
                    'drug_addiction' => 'any',
                    'smoke' => json_encode(['never']),
                    'alcohol' => json_encode(['never']),
                    'updated_at' => Carbon::now(),
                ]
            );
        }

        // Add some profile photos
        $allUsers = DB::table('users')->get();
        foreach ($allUsers as $index => $user) {
            if ($index % 3 === 0) { // Add photos for every third user
                DB::table('profile_photos')->insert([
                    'user_id' => $user->id,
                    'photo_url' => 'https://example.com/photos/' . strtolower(explode('@', $user->email)[0]) . '1.jpg',
                    'is_primary' => true,
                    'is_verified' => true,
                    'upload_date' => Carbon::now(),
                    'verification_date' => Carbon::now(),
                ]);

                // Add a secondary photo for some users
                if ($index % 6 === 0) {
                    DB::table('profile_photos')->insert([
                        'user_id' => $user->id,
                        'photo_url' => 'https://example.com/photos/' . strtolower(explode('@', $user->email)[0]) . '2.jpg',
                        'is_primary' => false,
                        'is_verified' => true,
                        'upload_date' => Carbon::now(),
                        'verification_date' => Carbon::now(),
                    ]);
                }
            }
        }

        // Add some sample interests sent
        $users = DB::table('users')->get();
        for ($i = 0; $i < 10; $i++) {
            $senderIndex = rand(0, count($users) - 1);
            $receiverIndex = rand(0, count($users) - 1);

            // Ensure sender and receiver are different
            while ($senderIndex === $receiverIndex) {
                $receiverIndex = rand(0, count($users) - 1);
            }

            DB::table('interests_sent')->insert([
                'sender_id' => $users[$senderIndex]->id,
                'receiver_id' => $users[$receiverIndex]->id,
                'status' => $this->getRandomInterestStatus(),
                'message' => $this->getRandomMessage(),
                'sent_at' => Carbon::now(),
            ]);
        }

        // Add some sample matches
        for ($i = 0; $i < 5; $i++) {
            $user1Index = rand(0, count($users) - 1);
            $user2Index = rand(0, count($users) - 1);

            // Ensure user1 and user2 are different
            while ($user1Index === $user2Index) {
                $user2Index = rand(0, count($users) - 1);
            }

            DB::table('matches')->insert([
                'user1_id' => $users[$user1Index]->id,
                'user2_id' => $users[$user2Index]->id,
                'match_score' => rand(75, 98) / 10, // Random score between 7.5 and 9.8
                'status' => $this->getRandomMatchStatus(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Add some sample messages
        for ($i = 0; $i < 5; $i++) {
            $senderIndex = rand(0, count($users) - 1);
            $receiverIndex = rand(0, count($users) - 1);

            // Ensure sender and receiver are different
            while ($senderIndex === $receiverIndex) {
                $receiverIndex = rand(0, count($users) - 1);
            }

            DB::table('messages')->insert([
                'sender_id' => $users[$senderIndex]->id,
                'receiver_id' => $users[$receiverIndex]->id,
                'message' => $this->getRandomMessage(),
                'sent_at' => Carbon::now(),
            ]);
        }

        // Add some sample profile views
        for ($i = 0; $i < 10; $i++) {
            $viewerIndex = rand(0, count($users) - 1);
            $viewedIndex = rand(0, count($users) - 1);

            // Ensure viewer and viewed are different
            while ($viewerIndex === $viewedIndex) {
                $viewedIndex = rand(0, count($users) - 1);
            }

            DB::table('profile_views')->insert([
                'viewer_id' => $users[$viewerIndex]->id,
                'viewed_profile_id' => $users[$viewedIndex]->id,
                'viewed_at' => Carbon::now(),
            ]);
        }

        // Add some sample shortlisted profiles
        for ($i = 0; $i < 8; $i++) {
            $userIndex = rand(0, count($users) - 1);
            $shortlistedIndex = rand(0, count($users) - 1);

            // Ensure user and shortlisted are different
            while ($userIndex === $shortlistedIndex) {
                $shortlistedIndex = rand(0, count($users) - 1);
            }

            DB::table('shortlisted_profiles')->insert([
                'user_id' => $users[$userIndex]->id,
                'shortlisted_user_id' => $users[$shortlistedIndex]->id,
                'notes' => 'Interesting profile with good compatibility',
                'created_at' => Carbon::now(),
            ]);
        }

        // Add subscription plans
        DB::table('subscription_plans')->insert([
            [
                'name' => 'Basic Plan',
                'duration_days' => 30,
                'price' => 499.00,
                'max_messages' => 10,
                'max_contacts' => 5,
                'can_view_contact' => false,
                'priority_listing' => false,
                'features' => json_encode(['Basic Profile Access', 'Limited Messages']),
                'is_active' => true,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Premium Plan',
                'duration_days' => 90,
                'price' => 999.00,
                'max_messages' => 50,
                'max_contacts' => 25,
                'can_view_contact' => true,
                'priority_listing' => true,
                'features' => json_encode(['Unlimited Profile Access', 'Priority Listing', 'Contact Info Access']),
                'is_active' => true,
                'created_at' => Carbon::now(),
            ],
            [
                'name' => 'Elite Plan',
                'duration_days' => 365,
                'price' => 2499.00,
                'max_messages' => null,
                'max_contacts' => null,
                'can_view_contact' => true,
                'priority_listing' => true,
                'features' => json_encode(['Everything in Premium', 'VIP Support', 'Featured Profile']),
                'is_active' => true,
                'created_at' => Carbon::now(),
            ]
        ]);

        // Add some user subscriptions
        $plans = DB::table('subscription_plans')->get();
        $activeUsers = DB::table('users')->limit(10)->get();

        foreach ($activeUsers as $user) {
            $randomPlan = $plans->random();
            DB::table('user_subscriptions')->insert([
                'user_id' => $user->id,
                'plan_id' => $randomPlan->id,
                'start_date' => Carbon::now()->subDays(rand(1, 30)),
                'end_date' => Carbon::now()->addDays($randomPlan->duration_days - rand(1, 30)),
                'status' => 'active',
                'amount_paid' => $randomPlan->price,
                'created_at' => Carbon::now(),
            ]);
        }

        // Add some payments
        $subscriptions = DB::table('user_subscriptions')->get();
        foreach ($subscriptions as $subscription) {
            DB::table('payments')->insert([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount' => $subscription->amount_paid,
                'payment_method' => $this->getRandomPaymentMethod(),
                'transaction_id' => 'TXN' . rand(100, 999),
                'status' => 'completed',
                'payment_date' => Carbon::now(),
                'created_at' => Carbon::now(),
            ]);
        }

        // Add some activity logs
        $recentUsers = DB::table('users')->limit(15)->get();
        foreach ($recentUsers as $user) {
            DB::table('activity_logs')->insert([
                'user_id' => $user->id,
                'action' => $this->getRandomAction(),
                'ip_address' => '192.168.1.' . rand(1, 254),
                'device_info' => $this->getRandomDeviceInfo(),
                'details' => json_encode(['os' => $this->getRandomOS(), 'browser' => $this->getRandomBrowser()]),
                'created_at' => Carbon::now(),
            ]);
        }
    }

    private function generateFatherName($lastName)
    {
        $prefixes = ['Muhammed', 'Ahmed', 'Ali', 'Hassan', 'Hussain', 'Umar', 'Usman', 'Abubakr', 'Zayd', 'Khalid', 'Bilal', 'Faisal', 'Rashid', 'Sameer', 'Shafi', 'Mansoor', 'Saleem', 'Anwar', 'Ashraf', 'Noufal'];
        return $prefixes[array_rand($prefixes)] . ' ' . $lastName;
    }

    private function generateMotherName($lastName)
    {
        $prefixes = ['Fatimah', 'Aisha', 'Zainab', 'Maryam', 'Khadija', 'Hana', 'Nadiya', 'Safiya', 'Rehana', 'Sumaiya', 'Amina', 'Naseema', 'Shamna', 'Shadiya', 'Fida', 'Liya', 'Sana', 'Meher', 'Arshida', 'Jasna'];
        return $prefixes[array_rand($prefixes)] . ' ' . $lastName;
    }

    private function getRandomOccupation()
    {
        $occupations = ['Business Owner', 'Government Officer', 'Engineer', 'Doctor', 'Teacher', 'Professor', 'Advocate', 'Farmer', 'Bank Manager', 'Retired Professor', 'Actor', 'Chartered Accountant', 'Nurse', 'Tailor', 'Interior Designer', 'Bank Clerk', 'Homemaker', 'Social Worker', 'Businesswoman', 'Accountant'];
        return $occupations[array_rand($occupations)];
    }

    private function getRandomMaleOccupation()
    {
        $occupations = ['Doctor', 'Engineer', 'Advocate', 'Business Owner', 'Professor', 'Chartered Accountant', 'Bank Manager', 'Actor', 'Government Officer', 'Farmer', 'Retired Professor'];
        return $occupations[array_rand($occupations)];
    }

    private function getRandomFemaleOccupation()
    {
        $occupations = ['Teacher', 'Nurse', 'Advocate', 'Homemaker', 'Housewife', 'Social Worker', 'Business Owner', 'Accountant', 'Tailor', 'Interior Designer', 'Bank Clerk', 'Professor'];
        return $occupations[array_rand($occupations)];
    }

    private function getRandomFamilyStatus()
    {
        $statuses = ['middle_class', 'upper_middle_class', 'rich'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomCity()
    {
        $cities = ['Kozhikode', 'Malappuram', 'Kochi', 'Thiruvananthapuram', 'Thrissur', 'Palakkad', 'Kannur', 'Alappuzha', 'Kottayam', 'Kasaragod', 'Idukki', 'Wayanad', 'Pathanamthitta', 'Kollam', 'Thalassery', 'Manjeri', 'Tirur', 'Ponnani', 'Vatagara', 'Perintalmanna'];
        return $cities[array_rand($cities)];
    }

    private function getRandomInterestStatus()
    {
        $statuses = ['pending', 'accepted', 'rejected', 'withdrawn'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomMessage()
    {
        $messages = [
            'Hi, I liked your profile. Would love to connect.',
            'Hello, great to meet you!',
            'Your profile looks interesting. Would you like to chat?',
            'Thank you for your interest. Looking forward to connecting.',
            'Thanks for accepting my interest. Let\'s get to know each other.',
            'Saw your profile and would like to know more.',
            'Your interests align well with mine.',
            'Interested in getting to know you better.',
            'Happy to connect and explore possibilities.',
            'Hope we can build a meaningful relationship.'
        ];
        return $messages[array_rand($messages)];
    }

    private function getRandomMatchStatus()
    {
        $statuses = ['suggested', 'contacted', 'in_progress', 'matched', 'rejected'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomPaymentMethod()
    {
        $methods = ['card', 'upi', 'netbanking', 'wallet'];
        return $methods[array_rand($methods)];
    }

    private function getRandomAction()
    {
        $actions = ['login', 'profile_update', 'interest_sent', 'message_sent', 'profile_view', 'subscription_purchase', 'photo_uploaded', 'preference_updated'];
        return $actions[array_rand($actions)];
    }

    private function getRandomDeviceInfo()
    {
        $devices = [
            'Chrome on Windows',
            'Safari on iPhone',
            'Firefox on Linux',
            'Chrome on Android',
            'Edge on Windows',
            'Safari on iPad',
            'Opera on Windows'
        ];
        return $devices[array_rand($devices)];
    }

    private function getRandomOS()
    {
        $oss = ['Windows', 'iOS', 'Linux', 'Android', 'macOS'];
        return $oss[array_rand($oss)];
    }

    private function getRandomBrowser()
    {
        $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge', 'Opera'];
        return $browsers[array_rand($browsers)];
    }
}