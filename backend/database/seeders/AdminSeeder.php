<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = strtolower((string) env('ADMIN_EMAIL', 'admin@petmarketplace.com'));
        $adminPassword = (string) env('ADMIN_PASSWORD', 'Admin@1234');
        $adminName = (string) env('ADMIN_NAME', 'admin');

        // Keep admin credentials consistent on every seed run.
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $this->command->info("Admin ready: {$adminEmail} / {$adminPassword}");
    }
}
