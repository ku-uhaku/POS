<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user to use as creator, or create a system user
        $admin = User::where('email', 'admin@example.com')->first();

        // Create main store
        $mainStore = Store::firstOrCreate(
            ['code' => 'STORE-001'],
            [
                'name' => 'Main Store',
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'postal_code' => '10001',
                'phone' => '+1-555-0100',
                'email' => 'main@store.com',
                'status' => 'active',
                'created_by' => $admin?->id,
            ]
        );

        // Create secondary store
        $secondaryStore = Store::firstOrCreate(
            ['code' => 'STORE-002'],
            [
                'name' => 'Secondary Store',
                'address' => '456 Oak Avenue',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '90001',
                'phone' => '+1-555-0200',
                'email' => 'secondary@store.com',
                'status' => 'active',
                'created_by' => $admin?->id,
            ]
        );

        $this->command->info('Stores seeded successfully!');
        $this->command->info("Created: {$mainStore->name} ({$mainStore->code})");
        $this->command->info("Created: {$secondaryStore->name} ({$secondaryStore->code})");
    }
}
