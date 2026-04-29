<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@petmarketplace.com'],
            [
                'name'      => 'Platform Admin',
                'password'  => Hash::make('Admin@1234'),
                'role'      => 'admin',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Admin: admin@petmarketplace.com / Admin@1234');
        $this->command->warn('   ⚠️  Change this password in production!');
    }
}
