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
        // Clear existing data in correct order (child tables first)
        DB::table('profile_photos')->delete();
        DB::table('interests_sent')->delete();
        DB::table('matches')->delete();
        DB::table('messages')->delete();
        DB::table('profile_views')->delete();
        DB::table('shortlisted_profiles')->delete();
        DB::table('payments')->delete();
        DB::table('user_subscriptions')->delete();
        DB::table('activity_logs')->delete();
        DB::table('reports')->delete();
        DB::table('notifications')->delete();
        DB::table('preferences')->delete();
        DB::table('family_details')->delete();
        DB::table('user_profiles')->delete();
        DB::table('users')->delete();

        // Define 30 female users
        $females = [
            ['Priya', 'Sharma', 'Hindu', 'Brahmin', 'Saraswat', 'Hindi', 'Fashion Designer', 'B.Des Fashion', 650000.00, 'Mumbai', 'Maharashtra'],
            ['Sneha', 'Verma', 'Hindu', 'Kshatriya', 'Rajput', 'Hindi', 'Dance Choreographer', 'BA Dance', 550000.00, 'Delhi', 'Delhi'],
            ['Pooja', 'Singh', 'Hindu', 'Vaishya', 'Aggarwal', 'Hindi', 'Yoga Instructor', 'M.Sc Yoga', 500000.00, 'Bangalore', 'Karnataka'],
            ['Kavya', 'Patel', 'Hindu', 'Gujjar', 'Patel', 'Gujarati', 'Nutritionist', 'B.Sc Nutrition', 450000.00, 'Ahmedabad', 'Gujarat'],
            ['Ananya', 'Jain', 'Jain', 'Jain', 'Digamber', 'Hindi', 'Psychologist', 'MA Psychology', 600000.00, 'Jaipur', 'Rajasthan'],
            ['Divya', 'Reddy', 'Hindu', 'Reddy', 'Kamma', 'Telugu', 'Content Writer', 'BA Journalism', 480000.00, 'Hyderabad', 'Telangana'],
            ['Simran', 'Malhotra', 'Hindu', 'Khatri', 'Ludhiana', 'Punjabi', 'Singer', 'BMus Vocal', 520000.00, 'Chandigarh', 'Punjab'],
            ['Neha', 'Nair', 'Hindu', 'Nair', 'Nair', 'Malayalam', 'Marine Biologist', 'M.Sc Marine Biology', 580000.00, 'Thiruvananthapuram', 'Kerala'],
            ['Ritu', 'Kumar', 'Hindu', 'Kayastha', 'Chattopadhyay', 'Bengali', 'Writer', 'MA Literature', 420000.00, 'Kolkata', 'West Bengal'],
            ['Shalini', 'Mehta', 'Hindu', 'Parsi', 'Parsi', 'Gujarati', 'Interior Designer', 'B.Des Interior', 620000.00, 'Mumbai', 'Maharashtra'],
            ['Komal', 'Chopra', 'Hindu', 'Bania', 'Aggarwal', 'Hindi', 'Nutrition Consultant', 'M.Sc Nutrition', 550000.00, 'Chennai', 'Tamil Nadu'],
            ['Preeti', 'Gupta', 'Hindu', 'Gupta', 'Gupta', 'Hindi', 'Digital Marketing Manager', 'MBA Marketing', 700000.00, 'Noida', 'Uttar Pradesh'],
            ['Swati', 'Shukla', 'Hindu', 'Kayastha', 'Shukla', 'Hindi', 'Environmental Scientist', 'PhD Environmental Studies', 650000.00, 'Lucknow', 'Uttar Pradesh'],
            ['Rashmi', 'Rao', 'Hindu', 'Brahmin', 'Iyengar', 'Tamil', 'Dance Teacher', 'BA Classical Dance', 480000.00, 'Coimbatore', 'Tamil Nadu'],
            ['Monika', 'Shetty', 'Hindu', 'Shetty', 'Billava', 'Kannada', 'Chef', 'Diploma Culinary Arts', 520000.00, 'Mangalore', 'Karnataka'],
            ['Anita', 'Kapoor', 'Hindu', 'Jat', 'Kapoor', 'Hindi', 'Fitness Trainer', 'M.Sc Sports Science', 500000.00, 'Gurgaon', 'Haryana'],
            ['Sheetal', 'Menon', 'Christian', 'Nair', 'Syrian Christian', 'Malayalam', 'School Teacher', 'MA Education', 450000.00, 'Kochi', 'Kerala'],
            ['Geeta', 'Iyer', 'Hindu', 'Brahmin', 'Iyer', 'Tamil', 'Advocate', 'LLB', 750000.00, 'Madurai', 'Tamil Nadu'],
            ['Rani', 'Bhatia', 'Hindu', 'Bhatia', 'Bhatia', 'Hindi', 'Art Therapist', 'BFA Fine Arts', 480000.00, 'Amritsar', 'Punjab'],
            ['Seema', 'Chauhan', 'Hindu', 'Thakur', 'Chauhan', 'Hindi', 'Conservationist', 'M.Sc Wildlife Biology', 550000.00, 'Dehradun', 'Uttarakhand'],
            ['Meera', 'Sharma', 'Hindu', 'Brahmin', 'Saraswat', 'Hindi', 'Data Analyst', 'M.Tech Data Science', 680000.00, 'Mumbai', 'Maharashtra'],
            ['Sunita', 'Verma', 'Hindu', 'Kshatriya', 'Rajput', 'Hindi', 'HR Manager', 'MBA HR', 620000.00, 'Delhi', 'Delhi'],
            ['Kavita', 'Singh', 'Hindu', 'Vaishya', 'Aggarwal', 'Hindi', 'Event Planner', 'BA Event Management', 580000.00, 'Bangalore', 'Karnataka'],
            ['Rajni', 'Patel', 'Hindu', 'Gujjar', 'Patel', 'Gujarati', 'Social Worker', 'MA Social Work', 420000.00, 'Ahmedabad', 'Gujarat'],
            ['Poonam', 'Jain', 'Jain', 'Jain', 'Shwetamber', 'Hindi', 'Astrologer', 'MA Astrology', 450000.00, 'Jaipur', 'Rajasthan'],
            ['Sarita', 'Reddy', 'Hindu', 'Reddy', 'Kamma', 'Telugu', 'Web Developer', 'B.Tech Computer Science', 720000.00, 'Hyderabad', 'Telangana'],
            ['Anjali', 'Malhotra', 'Hindu', 'Khatri', 'Ludhiana', 'Punjabi', 'Brand Manager', 'MBA Marketing', 780000.00, 'Chandigarh', 'Punjab'],
            ['Neeta', 'Nair', 'Hindu', 'Nair', 'Nair', 'Malayalam', 'Ayurvedic Doctor', 'BAMS', 600000.00, 'Thiruvananthapuram', 'Kerala'],
            ['Lata', 'Kumar', 'Hindu', 'Kayastha', 'Chattopadhyay', 'Bengali', 'Translator', 'MA Linguistics', 520000.00, 'Kolkata', 'West Bengal'],
            ['Uma', 'Mehta', 'Hindu', 'Parsi', 'Parsi', 'Gujarati', 'CEO', 'MBA Business', 1200000.00, 'Mumbai', 'Maharashtra'],
        ];

        // Define 20 male users
        $males = [
            ['Rahul', 'Sharma', 'Hindu', 'Brahmin', 'Saraswat', 'Hindi', 'Software Developer', 'M.Tech Computer Science', 800000.00, 'Mumbai', 'Maharashtra'],
            ['Amit', 'Verma', 'Hindu', 'Kshatriya', 'Rajput', 'Hindi', 'Business Analyst', 'B.Com', 750000.00, 'Delhi', 'Delhi'],
            ['Vikram', 'Singh', 'Hindu', 'Vaishya', 'Aggarwal', 'Hindi', 'Financial Consultant', 'MBA Finance', 900000.00, 'Bangalore', 'Karnataka'],
            ['Arjun', 'Patel', 'Hindu', 'Gujjar', 'Patel', 'Gujarati', 'Mechanical Engineer', 'B.Tech Mechanical', 650000.00, 'Ahmedabad', 'Gujarat'],
            ['Rohit', 'Jain', 'Jain', 'Jain', 'Shwetamber', 'Hindi', 'Chartered Accountant', 'CA', 1000000.00, 'Jaipur', 'Rajasthan'],
            ['Suresh', 'Reddy', 'Hindu', 'Reddy', 'Kamma', 'Telugu', 'Network Administrator', 'B.Tech ECE', 600000.00, 'Hyderabad', 'Telangana'],
            ['Karan', 'Malhotra', 'Hindu', 'Khatri', 'Ludhiana', 'Punjabi', 'Marketing Manager', 'MBA Marketing', 850000.00, 'Chandigarh', 'Punjab'],
            ['Ajay', 'Nair', 'Hindu', 'Nair', 'Nair', 'Malayalam', 'Architect', 'B.Arch', 700000.00, 'Thiruvananthapuram', 'Kerala'],
            ['Manoj', 'Kumar', 'Hindu', 'Kayastha', 'Chattopadhyay', 'Bengali', 'Economist', 'MA Economics', 720000.00, 'Kolkata', 'West Bengal'],
            ['Rajesh', 'Mehta', 'Hindu', 'Parsi', 'Parsi', 'Gujarati', 'Business Owner', 'MBA Operations', 950000.00, 'Mumbai', 'Maharashtra'],
            ['Deepak', 'Chopra', 'Hindu', 'Bania', 'Aggarwal', 'Hindi', 'Doctor', 'MD', 1200000.00, 'Chennai', 'Tamil Nadu'],
            ['Sanjay', 'Gupta', 'Hindu', 'Gupta', 'Gupta', 'Hindi', 'IT Specialist', 'B.Tech IT', 680000.00, 'Noida', 'Uttar Pradesh'],
            ['Vivek', 'Shukla', 'Hindu', 'Kayastha', 'Shukla', 'Hindi', 'Research Scientist', 'PhD Environmental Science', 780000.00, 'Lucknow', 'Uttar Pradesh'],
            ['Prakash', 'Rao', 'Hindu', 'Brahmin', 'Iyengar', 'Tamil', 'Music Teacher', 'MA Music', 550000.00, 'Coimbatore', 'Tamil Nadu'],
            ['Ravi', 'Shetty', 'Hindu', 'Shetty', 'Billava', 'Kannada', 'Civil Engineer', 'B.Tech Civil', 620000.00, 'Mangalore', 'Karnataka'],
            ['Anil', 'Kapoor', 'Hindu', 'Jat', 'Kapoor', 'Hindi', 'Physics Tutor', 'B.Sc Physics', 500000.00, 'Gurgaon', 'Haryana'],
            ['Sunil', 'Menon', 'Christian', 'Nair', 'Syrian Christian', 'Malayalam', 'Chemical Engineer', 'M.Tech Chemical', 750000.00, 'Kochi', 'Kerala'],
            ['Ramesh', 'Iyer', 'Hindu', 'Brahmin', 'Iyer', 'Tamil', 'Electronics Engineer', 'BE Electronics', 650000.00, 'Madurai', 'Tamil Nadu'],
            ['Naresh', 'Bhatia', 'Hindu', 'Bhatia', 'Bhatia', 'Hindi', 'Historian', 'MA History', 580000.00, 'Amritsar', 'Punjab'],
            ['Pankaj', 'Chauhan', 'Hindu', 'Thakur', 'Chauhan', 'Hindi', 'Wildlife Photographer', 'M.Sc Zoology', 600000.00, 'Dehradun', 'Uttarakhand'],
        ];

        // Fetch reference data for faster lookups
        $religionsList = DB::table('religions')->pluck('id', 'name')->toArray();
        $castesList = DB::table('castes')->get()->groupBy('religion_id')->map(function ($items) {
            return $items->pluck('id', 'name')->toArray();
        })->toArray();
        $subCastesList = DB::table('sub_castes')->get()->groupBy('caste_id')->map(function ($items) {
            return $items->pluck('id', 'name')->toArray();
        })->toArray();
        $educationsList = DB::table('education')->pluck('id', 'name')->toArray();
        $occupationsList = DB::table('occupations')->pluck('id', 'name')->toArray();

        // Create female users
        foreach ($females as $index => $female) {
            $email = 'rejy' . ($index + 1) . '@yopmail.com';

            $userId = DB::table('users')->insertGetId([
                'email' => $email,
                'phone' => '+9198765432' . str_pad($index + 100, 2, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'email_verified' => true,
                'phone_verified' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $religionId = $religionsList[$female[2]] ?? null;
            $casteId = ($religionId && isset($castesList[$religionId])) ? ($castesList[$religionId][$female[3]] ?? null) : null;
            $subCasteId = ($casteId && isset($subCastesList[$casteId])) ? ($subCastesList[$casteId][$female[4]] ?? null) : null;

            // Education/Occupation lookup
            $eduName = $female[7];
            // Simple normalization for seeder data
            if ($eduName == 'M.Tech Computer Science')
                $eduName = 'M.Tech';
            if ($eduName == 'M.Sc Nutrition')
                $eduName = 'M.Sc';
            if ($eduName == 'M.Sc Yoga')
                $eduName = 'M.Sc';
            if ($eduName == 'M.Sc Marine Biology')
                $eduName = 'M.Sc';
            if ($eduName == 'M.Sc Sports Science')
                $eduName = 'M.Sc';
            if ($eduName == 'M.Sc Wildlife Biology')
                $eduName = 'M.Sc';
            if ($eduName == 'M.Tech Data Science')
                $eduName = 'M.Tech';

            $educationId = $educationsList[$eduName] ?? ($educationsList['Graduate'] ?? null);
            $occupationId = $occupationsList[$female[6]] ?? ($occupationsList['Other'] ?? null);

            DB::table('user_profiles')->insert([
                'user_id' => $userId,
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
                'drug_addiction' => false,
                'smoke' => 'never',
                'alcohol' => 'never',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('family_details')->insert([
                'user_id' => $userId,
                'father_name' => $this->generateFatherName($female[1]),
                'father_occupation' => $this->getRandomOccupation(),
                'mother_name' => $this->generateMotherName($female[1]),
                'mother_occupation' => $this->getRandomFemaleOccupation(),
                'siblings' => rand(0, 3),
                'family_type' => rand(0, 1) ? 'nuclear' : 'joint',
                'family_status' => $this->getRandomFamilyStatus(),
                'family_location' => $female[9],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('preferences')->insert([
                'user_id' => $userId,
                'min_age' => 25,
                'max_age' => 35,
                'min_height' => 170,
                'max_height' => 188,
                'marital_status' => 'never_married',
                'religion_id' => $religionId,
                'caste_ids' => json_encode($casteId ? [$casteId] : []),
                'sub_caste_ids' => json_encode($subCasteId ? [$subCasteId] : []),
                'education_ids' => json_encode($educationId ? [$educationId] : []),
                'occupation_ids' => json_encode($occupationId ? [$occupationId] : []),
                'min_income' => 500000.00,
                'max_income' => 1500000.00,
                'preferred_locations' => json_encode([$female[9], $this->getRandomCity(), $this->getRandomCity()]),
                'drug_addiction' => 'any',
                'smoke' => json_encode(['never']),
                'alcohol' => json_encode(['never']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Create male users
        foreach ($males as $index => $male) {
            $email = 'rejy' . ($index + 31) . '@yopmail.com'; // Males start from rejy31

            $userId = DB::table('users')->insertGetId([
                'email' => $email,
                'phone' => '+9198765432' . str_pad($index + 130, 2, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'),
                'role' => 'user',
                'status' => 'active',
                'email_verified' => true,
                'phone_verified' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            $religionId = $religionsList[$male[2]] ?? null;
            $casteId = ($religionId && isset($castesList[$religionId])) ? ($castesList[$religionId][$male[3]] ?? null) : null;
            $subCasteId = ($casteId && isset($subCastesList[$casteId])) ? ($subCastesList[$casteId][$male[4]] ?? null) : null;

            // Education/Occupation lookup
            $eduName = $male[7];
            // Simple normalization for seeder data
            if ($eduName == 'M.Tech Computer Science')
                $eduName = 'M.Tech';
            if ($eduName == 'BE Electronics')
                $eduName = 'B.Tech/B.E';
            if ($eduName == 'M.Tech Chemical')
                $eduName = 'M.Tech';

            $educationId = $educationsList[$eduName] ?? ($educationsList['Graduate'] ?? null);
            $occupationId = $occupationsList[$male[6]] ?? ($occupationsList['Other'] ?? null);

            DB::table('user_profiles')->insert([
                'user_id' => $userId,
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
                'drug_addiction' => false,
                'smoke' => 'never',
                'alcohol' => 'never',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('family_details')->insert([
                'user_id' => $userId,
                'father_name' => $this->generateFatherName($male[1]),
                'father_occupation' => $this->getRandomMaleOccupation(),
                'mother_name' => $this->generateMotherName($male[1]),
                'mother_occupation' => $this->getRandomFemaleOccupation(),
                'siblings' => rand(0, 3),
                'family_type' => rand(0, 1) ? 'nuclear' : 'joint',
                'family_status' => $this->getRandomFamilyStatus(),
                'family_location' => $male[9],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            DB::table('preferences')->insert([
                'user_id' => $userId,
                'min_age' => 22,
                'max_age' => 32,
                'min_height' => 155,
                'max_height' => 170,
                'marital_status' => 'never_married',
                'religion_id' => $religionId,
                'caste_ids' => json_encode($casteId ? [$casteId] : []),
                'sub_caste_ids' => json_encode($subCasteId ? [$subCasteId] : []),
                'education_ids' => json_encode($educationId ? [$educationId] : []),
                'occupation_ids' => json_encode($occupationId ? [$occupationId] : []),
                'min_income' => 400000.00,
                'max_income' => 1200000.00,
                'preferred_locations' => json_encode([$male[9], $this->getRandomCity(), $this->getRandomCity()]),
                'drug_addiction' => 'any',
                'smoke' => json_encode(['never']),
                'alcohol' => json_encode(['never']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
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
        $prefixes = ['Raj', 'Ramesh', 'Rajesh', 'Vijay', 'Rakesh', 'Ashok', 'Venkat', 'Raj', 'Suresh', 'Gopal', 'Thomas', 'Ravi', 'Raghav', 'Anil', 'Sunil', 'Ramesh'];
        return $prefixes[array_rand($prefixes)] . ' ' . $lastName;
    }

    private function generateMotherName($lastName)
    {
        $prefixes = ['Sunita', 'Sushma', 'Poonam', 'Kavita', 'Meena', 'Lakshmi', 'Sarita', 'Latha', 'Sushila', 'Pushpa', 'Alice', 'Lakshmi', 'Sunita', 'Shobha', 'Kamala', 'Alice'];
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
        $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Hyderabad', 'Ahmedabad', 'Chennai', 'Kolkata', 'Surat', 'Pune', 'Jaipur', 'Nagpur', 'Patna', 'Ghaziabad', 'Bhopal', 'Indore', 'Vadodara', 'Coimbatore', 'Chandigarh', 'Gurgaon', 'Thiruvananthapuram', 'Kochi', 'Mangalore', 'Dehradun', 'Amritsar', 'Noida', 'Lucknow', 'Kanpur', 'Mysore', 'Vijayawada', 'Warangal', 'Madurai', 'Ludhiana', 'Gujranwala'];
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