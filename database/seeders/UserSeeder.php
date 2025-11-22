<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'EMP-00001',
                'status' => 'active',
                'hire_date' => now()->subYears(2),
            ]
        );

        // Assign admin role to admin user
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'EMP-00002',
                'status' => 'active',
                'hire_date' => now()->subYear(),
            ]
        );

        // Assign user role to test user
        if (! $user->hasRole('user')) {
            $user->assignRole('user');
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Admin: admin@example.com / password (has admin role)');
        $this->command->info('Test: test@example.com / password (has user role)');
    }
}
