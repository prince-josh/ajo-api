<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'superadmin@ajosavings.com'],
            [
                'first_name'     => 'Super',
                'last_name'      => 'Admin',
                'email'          => 'superadmin@ajosavings.com',
                'phone'          => '08000000000',
                'password'       => Hash::make('SuperAdmin@2024!'),
                'account_number' => '0000000001',
                'role'           => 'super_admin',
                'is_active'      => true,
                'is_verified'    => true,
            ]
        );

        // Create wallet for super admin
        Wallet::firstOrCreate(
            ['user_id' => $admin->id],
            [
                'balance'         => 0.00,
                'total_saved'     => 0.00,
                'total_withdrawn' => 0.00,
            ]
        );

        $this->command->info('✅ Super Admin seeded successfully.');
        $this->command->info("   Email: superadmin@ajosavings.com");
        $this->command->info("   Password: SuperAdmin@2024!");
        $this->command->warn("   ⚠️  Change the default password after first login!");
    }
}
