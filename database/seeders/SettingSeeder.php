<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Store;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Default settings for each store.
     */
    protected array $defaultSettings = [
        'currency' => ['value' => 'USD', 'type' => 'string'],
        'currency_position' => ['value' => 'before', 'type' => 'string'],
        'date_format' => ['value' => 'Y-m-d', 'type' => 'string'],
        'time_format' => ['value' => 'H:i:s', 'type' => 'string'],
        'timezone' => ['value' => 'UTC', 'type' => 'string'],
        'language' => ['value' => 'en', 'type' => 'string'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();

        foreach ($stores as $store) {
            foreach ($this->defaultSettings as $key => $setting) {
                Setting::firstOrCreate(
                    [
                        'store_id' => $store->id,
                        'key' => $key,
                    ],
                    [
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                    ]
                );
            }
        }

        $this->command->info('Settings seeded successfully for '.$stores->count().' store(s)!');
    }
}
