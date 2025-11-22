<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $settingKeys = [
            'currency' => ['value' => fake()->randomElement(['USD', 'EUR', 'GBP', 'JPY', 'CAD']), 'type' => 'string'],
            'currency_position' => ['value' => fake()->randomElement(['before', 'after']), 'type' => 'string'],
            'date_format' => ['value' => fake()->randomElement(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y']), 'type' => 'string'],
            'time_format' => ['value' => fake()->randomElement(['H:i:s', 'h:i A', 'H:i']), 'type' => 'string'],
            'timezone' => ['value' => fake()->randomElement(['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo']), 'type' => 'string'],
            'language' => ['value' => fake()->randomElement(['en', 'fr', 'ar', 'es', 'de']), 'type' => 'string'],
        ];

        $key = fake()->randomElement(array_keys($settingKeys));
        $setting = $settingKeys[$key];

        return [
            'store_id' => \App\Models\Store::factory(),
            'key' => $key,
            'value' => $setting['value'],
            'type' => $setting['type'],
        ];
    }

    /**
     * Create a currency setting.
     */
    public function currency(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'currency',
            'value' => fake()->randomElement(['USD', 'EUR', 'GBP', 'JPY', 'CAD']),
            'type' => 'string',
        ]);
    }

    /**
     * Create a currency_position setting.
     */
    public function currencyPosition(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'currency_position',
            'value' => fake()->randomElement(['before', 'after']),
            'type' => 'string',
        ]);
    }

    /**
     * Create a date_format setting.
     */
    public function dateFormat(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'date_format',
            'value' => fake()->randomElement(['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y']),
            'type' => 'string',
        ]);
    }

    /**
     * Create a time_format setting.
     */
    public function timeFormat(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'time_format',
            'value' => fake()->randomElement(['H:i:s', 'h:i A', 'H:i']),
            'type' => 'string',
        ]);
    }

    /**
     * Create a timezone setting.
     */
    public function timezone(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'timezone',
            'value' => fake()->randomElement(['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo']),
            'type' => 'string',
        ]);
    }

    /**
     * Create a language setting.
     */
    public function language(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'language',
            'value' => fake()->randomElement(['en', 'fr', 'ar', 'es', 'de']),
            'type' => 'string',
        ]);
    }
}
