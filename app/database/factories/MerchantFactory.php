<?php

namespace Database\Factories;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'salla_merchant_id' => 'merchant_' . $this->faker->unique()->numberBetween(100000, 999999),
            'store_name' => $this->faker->company(),
            'n8n_base_url' => 'https://n8n-' . $this->faker->slug(2) . '.example.com',
            'n8n_path' => '/webhook/salla',
            'n8n_auth_type' => $this->faker->randomElement(['none', 'bearer', 'basic']),
            'n8n_bearer_token' => $this->faker->sha256(),
            'is_active' => true,
            'last_ping_ok_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withBasicAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'n8n_auth_type' => 'basic',
            'n8n_basic_user' => $this->faker->userName(),
            'n8n_basic_pass' => $this->faker->password(),
        ]);
    }
}