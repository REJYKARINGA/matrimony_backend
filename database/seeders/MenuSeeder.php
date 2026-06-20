<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            // Management
            ['path' => '/dashboard', 'label' => 'Dashboard', 'group' => 'Management', 'sort_order' => 1],
            ['path' => '/users', 'label' => 'Users', 'group' => 'Management', 'sort_order' => 2],
            ['path' => '/user-profiles', 'label' => 'User Profiles', 'group' => 'Management', 'sort_order' => 3],

            // Safety & Security
            ['path' => '/photo-requests', 'label' => 'Photo Access Monitoring', 'group' => 'Safety & Security', 'sort_order' => 10],
            ['path' => '/verifications', 'label' => 'ID Verifications', 'group' => 'Safety & Security', 'sort_order' => 11],
            ['path' => '/photo-verifications', 'label' => 'Photo Verifications', 'group' => 'Safety & Security', 'sort_order' => 12],
            ['path' => '/profile-verifications', 'label' => 'Profile Modifications', 'group' => 'Safety & Security', 'sort_order' => 13],
            ['path' => '/reports', 'label' => 'User Reports & Flags', 'group' => 'Safety & Security', 'sort_order' => 14],

            // Financial
            ['path' => '/payments', 'label' => 'Payments', 'group' => 'Financial', 'sort_order' => 20],
            ['path' => '/wallet-transactions', 'label' => 'Wallet Transactions', 'group' => 'Financial', 'sort_order' => 21],
            ['path' => '/abandoned-payments', 'label' => 'Abandoned Payments', 'group' => 'Financial', 'sort_order' => 22],
            ['path' => '/payment-verifications', 'label' => 'Payment Verifications', 'group' => 'Financial', 'sort_order' => 23],
            ['path' => '/recharge-tiers', 'label' => 'Recharge Tiers', 'group' => 'Financial', 'sort_order' => 24],

            // App Content
            ['path' => '/success-stories', 'label' => 'Success Stories', 'group' => 'App Content', 'sort_order' => 30],
            ['path' => '/engagement-posters', 'label' => 'Engagement Posters', 'group' => 'App Content', 'sort_order' => 31],
            ['path' => '/suggestions', 'label' => 'User Suggestions', 'group' => 'App Content', 'sort_order' => 32],

            // Data Management
            ['path' => '/education', 'label' => 'Education', 'group' => 'Data Management', 'sort_order' => 40],
            ['path' => '/occupation', 'label' => 'Occupation', 'group' => 'Data Management', 'sort_order' => 41],
            ['path' => '/interests', 'label' => 'Interests & Hobbies', 'group' => 'Data Management', 'sort_order' => 42],
            ['path' => '/personalities', 'label' => 'Personality Traits', 'group' => 'Data Management', 'sort_order' => 43],
            ['path' => '/religion-management', 'label' => 'Religion & Community', 'group' => 'Data Management', 'sort_order' => 44],
            ['path' => '/family-details', 'label' => 'Family Details', 'group' => 'Data Management', 'sort_order' => 45],

            // Settings & Logs
            ['path' => '/theme-settings', 'label' => 'Theme Settings', 'group' => 'Settings & Logs', 'sort_order' => 50],
            ['path' => '/promotion-settings', 'label' => 'Promotion Settings', 'group' => 'Settings & Logs', 'sort_order' => 51],
            ['path' => '/mediator-promotions', 'label' => 'Mediator Promotions', 'group' => 'Settings & Logs', 'sort_order' => 52],
            ['path' => '/admin-settings', 'label' => 'Admin Settings', 'group' => 'Settings & Logs', 'sort_order' => 53],
            ['path' => '/festivals', 'label' => 'Festival Offers', 'group' => 'Settings & Logs', 'sort_order' => 54],
            ['path' => '/contact-unlock-requests', 'label' => 'Unlock Requests', 'group' => 'Settings & Logs', 'sort_order' => 55],
            ['path' => '/contact-unlocks', 'label' => 'Contact Unlocks', 'group' => 'Settings & Logs', 'sort_order' => 56],
            ['path' => '/preferences', 'label' => 'Preferences', 'group' => 'Settings & Logs', 'sort_order' => 57],
            ['path' => '/audit-logs', 'label' => 'Login & Activity Logs', 'group' => 'Settings & Logs', 'sort_order' => 58],
            ['path' => '/permissions', 'label' => 'Permissions', 'group' => 'Settings & Logs', 'sort_order' => 59],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(['path' => $menu['path']], $menu);
        }
    }
}
