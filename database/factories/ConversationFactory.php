<?php

namespace Database\Factories;

use App\Domains\Conversation\Models\Conversation;
use App\Domains\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'channel' => fake()->randomElement(['messenger', 'whatsapp', 'comment', 'email']),
            'message' => fake()->realText(100),
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'metadata' => ['intent' => fake()->word()],
        ];
    }

    public function inbound(): static
    {
        return $this->state(fn () => ['direction' => 'inbound']);
    }

    public function outbound(): static
    {
        return $this->state(fn () => ['direction' => 'outbound']);
    }
}
