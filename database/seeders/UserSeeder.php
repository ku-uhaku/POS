<?php

namespace Database\Seeders;

use App\Models\Store;
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
        // Get stores (should be seeded before users)
        $mainStore = Store::where('code', 'STORE-001')->first();
        $secondaryStore = Store::where('code', 'STORE-002')->first();

        if (! $mainStore) {
            $this->command->warn('No stores found. Please run StoreSeeder first.');
        }

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
                'store_id' => $mainStore?->id,
                'default_store_id' => $mainStore?->id,
            ]
        );

        // Assign admin role
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Assign admin to all stores (admin has access to all stores)
        if ($mainStore) {
            $allStores = Store::all();
            foreach ($allStores as $store) {
                if (! $admin->stores()->where('stores.id', $store->id)->exists()) {
                    $admin->stores()->attach($store->id);
                }
            }
            $this->command->info("✓ Admin assigned to {$allStores->count()} store(s)");
        }

        // Create test user (regular user)
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'EMP-00002',
                'status' => 'active',
                'hire_date' => now()->subYear(),
                'store_id' => $mainStore?->id,
                'default_store_id' => $mainStore?->id,
            ]
        );

        // Assign user role
        if (! $testUser->hasRole('user')) {
            $testUser->assignRole('user');
        }

        // Assign test user to main store only
        if ($mainStore && ! $testUser->stores()->where('stores.id', $mainStore->id)->exists()) {
            $testUser->stores()->attach($mainStore->id);
            $this->command->info("✓ Test user assigned to {$mainStore->name}");
        }

        // Create manager user (has access to multiple stores)
        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'first_name' => 'Manager',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'EMP-00003',
                'status' => 'active',
                'hire_date' => now()->subMonths(6),
                'store_id' => $mainStore?->id,
                'default_store_id' => $mainStore?->id,
            ]
        );

        // Assign user role (can be changed to a manager role later)
        if (! $manager->hasRole('user')) {
            $manager->assignRole('user');
        }

        // Assign manager to both stores
        if ($mainStore && ! $manager->stores()->where('stores.id', $mainStore->id)->exists()) {
            $manager->stores()->attach($mainStore->id);
        }
        if ($secondaryStore && ! $manager->stores()->where('stores.id', $secondaryStore->id)->exists()) {
            $manager->stores()->attach($secondaryStore->id);
        }
        if ($mainStore || $secondaryStore) {
            $assignedStores = $manager->stores()->count();
            $this->command->info("✓ Manager assigned to {$assignedStores} store(s)");
        }

        // Output summary
        $this->command->newLine();
        $this->command->info('Users seeded successfully!');
        $this->command->newLine();
        $this->command->table(
            ['Email', 'Password', 'Role', 'Stores Access', 'Default Store'],
            [
                [
                    'admin@example.com',
                    'password',
                    'admin',
                    $admin->stores()->count().' store(s)',
                    $admin->defaultStore?->name ?? 'N/A',
                ],
                [
                    'test@example.com',
                    'password',
                    'user',
                    $testUser->stores()->count().' store(s)',
                    $testUser->defaultStore?->name ?? 'N/A',
                ],
                [
                    'manager@example.com',
                    'password',
                    'user',
                    $manager->stores()->count().' store(s)',
                    $manager->defaultStore?->name ?? 'N/A',
                ],
            ]
        );
    }
}
