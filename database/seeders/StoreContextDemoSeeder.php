<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreContextDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure core permissions/roles exist
        $this->call(RolePermissionSeeder::class);
        $this->call(StoreSeeder::class);

        // Create demo stores (idempotent)
        $hqStore = Store::firstOrCreate(
            ['code' => 'DEMO-HQ'],
            [
                'name' => 'Demo HQ',
                'address' => '100 Main St',
                'city' => 'Metropolis',
                'state' => 'NA',
                'country' => 'US',
                'postal_code' => '10000',
                'phone' => '+1-555-0100',
                'email' => 'hq@example.com',
                'status' => 'active',
            ]
        );

        $branchStore = Store::firstOrCreate(
            ['code' => 'DEMO-BRANCH'],
            [
                'name' => 'Demo Branch',
                'address' => '200 Market Ave',
                'city' => 'Gotham',
                'state' => 'NA',
                'country' => 'US',
                'postal_code' => '20000',
                'phone' => '+1-555-0200',
                'email' => 'branch@example.com',
                'status' => 'active',
            ]
        );

        // Admin with access to every store
        $admin = User::firstOrCreate(
            ['email' => 'demo-admin@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'DEMO-ADMIN',
                'status' => 'active',
                'hire_date' => now()->subYears(2),
                'store_id' => $hqStore->id,
                'default_store_id' => $hqStore->id,
            ]
        );
        $admin->assignRole('admin');
        $admin->stores()->syncWithoutDetaching([$hqStore->id, $branchStore->id]);

        // Manager who defaults to branch but has fallback to HQ
        $manager = User::firstOrCreate(
            ['email' => 'demo-manager@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Manager',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'DEMO-MANAGER',
                'status' => 'active',
                'hire_date' => now()->subYear(),
                'store_id' => $branchStore->id,
                'default_store_id' => $branchStore->id,
            ]
        );
        $manager->assignRole('user');
        $manager->stores()->syncWithoutDetaching([$hqStore->id, $branchStore->id]);

        // Cashier only tied to HQ
        $cashier = User::firstOrCreate(
            ['email' => 'demo-cashier@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Cashier',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'DEMO-CASHIER',
                'status' => 'active',
                'hire_date' => now()->subMonths(6),
                'store_id' => $hqStore->id,
                'default_store_id' => $hqStore->id,
            ]
        );
        $cashier->assignRole('user');
        $cashier->stores()->syncWithoutDetaching([$hqStore->id]);

        // Sales rep only tied to Branch
        $sales = User::firstOrCreate(
            ['email' => 'demo-sales@example.com'],
            [
                'first_name' => 'Demo',
                'last_name' => 'Sales',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'employee_id' => 'DEMO-SALES',
                'status' => 'active',
                'hire_date' => now()->subMonths(3),
                'store_id' => $branchStore->id,
                'default_store_id' => $branchStore->id,
            ]
        );
        $sales->assignRole('user');
        $sales->stores()->syncWithoutDetaching([$branchStore->id]);

        $this->command?->info('Demo stores and users seeded. Log in with:');
        $this->command?->table(
            ['Email', 'Password', 'Role', 'Default Store', 'Stores Access'],
            [
                [
                    'Email' => 'demo-admin@example.com',
                    'Password' => 'password',
                    'Role' => 'admin',
                    'Default Store' => $hqStore->name,
                    'Stores Access' => 'HQ & Branch',
                ],
                [
                    'Email' => 'demo-manager@example.com',
                    'Password' => 'password',
                    'Role' => 'user',
                    'Default Store' => $branchStore->name,
                    'Stores Access' => 'HQ & Branch',
                ],
                [
                    'Email' => 'demo-cashier@example.com',
                    'Password' => 'password',
                    'Role' => 'user',
                    'Default Store' => $hqStore->name,
                    'Stores Access' => 'HQ only',
                ],
                [
                    'Email' => 'demo-sales@example.com',
                    'Password' => 'password',
                    'Role' => 'user',
                    'Default Store' => $branchStore->name,
                    'Stores Access' => 'Branch only',
                ],
            ]
        );
    }
}
