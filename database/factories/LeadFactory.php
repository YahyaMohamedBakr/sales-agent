<?php

namespace Database\Factories;

use App\Domains\Campaign\Models\Campaign;
use App\Domains\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'psid' => fake()->uuid(),
            'name' => fake()->name(),
            'phone' => '05' . fake()->numerify('########'),
            'email' => fake()->email(),
            'source' => fake()->randomElement(['comment', 'messenger', 'whatsapp', 'email']),
            'campaign_id' => Campaign::factory(),
            'score' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement(['new', 'contacted', 'qualifying', 'qualified', 'converted', 'lost']),
            'metadata' => ['city' => fake()->randomElement(['الرياض', 'جدة', 'مكة', 'الدمام'])],
        ];
    }

    public function qualified(): static
    {
        return $this->state(fn () => [
            'score' => fake()->numberBetween(70, 100),
            'status' => 'qualified',
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn () => [
            'score' => fake()->numberBetween(70, 100),
            'status' => 'converted',
        ]);
    }

    public function fromComment(): static
    {
        return $this->state(fn () => ['source' => 'comment', 'psid' => fake()->uuid()]);
    }
}
