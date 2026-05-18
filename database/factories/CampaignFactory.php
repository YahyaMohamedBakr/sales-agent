<?php

namespace Database\Factories;

use App\Domains\Campaign\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Campaign',
            'status' => fake()->randomElement(['draft', 'active', 'paused', 'completed']),
            'platform' => 'facebook',
            'metadata' => ['budget' => fake()->numberBetween(100, 10000)],
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
}
