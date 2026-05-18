<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Demo User 1',
                'email' => 'demo-user-1@petmarketplace.com',
                'password' => 'Password@123',
            ],
            [
                'name' => 'Demo User 2',
                'email' => 'demo-user-2@petmarketplace.com',
                'password' => 'Password@123',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role' => 'user',
                    'is_active' => true,
                ],
            );
        }

        $this->command->info('Demo users ready: demo-user-1@petmarketplace.com, demo-user-2@petmarketplace.com / Password@123');
    }
}
