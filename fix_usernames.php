<?php
require_once __DIR__.'/vendor/autoload.php';

// Create a simple script to run in the Laravel context
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all users
$users = DB::table('users')->get();

foreach($users as $user) {
    if (empty($user->username) || $user->username === '') {
        $username = !empty($user->email) ? explode('@', $user->email)[0] : 'user_' . $user->id;
        $originalUsername = $username;
        $counter = 1;
        
        // Check for duplicates and append counter if needed
        while(DB::table('users')->where('id', '!=', $user->id)->where('username', $username)->count() > 0) {
            $username = $originalUsername . '_' . $counter;
            $counter++;
        }
        
        DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        echo "Updated user {$user->id} with username: $username\n";
    }
}