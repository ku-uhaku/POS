<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $stores = Store::factory()->count(2)->create();
        }

        $stores->each(function (Store $store): void {
            Contact::factory()
                ->count(5)
                ->state([
                    'store_id' => $store->id,
                    'type' => 'client',
                ])
                ->create();

            Contact::factory()
                ->count(2)
                ->state([
                    'store_id' => $store->id,
                    'type' => 'supplier',
                    'client_type' => 'company',
                ])
                ->create();
        });
    }
}
