<?php

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['client', 'supplier']);
        $clientType = $this->faker->randomElement(['individual', 'company', 'government', 'nonprofit']);

        return [
            'store_id' => Store::factory(),
            'type' => $type,
            'client_type' => $clientType,
            'company_name' => $clientType === 'individual' ? null : $this->faker->company(),
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'country' => $this->faker->country(),
            'postal_code' => $this->faker->postcode(),
            'tax_id' => $this->faker->numerify('TAX-####'),
            'notes' => $this->faker->sentence(),
        ];
    }
}
