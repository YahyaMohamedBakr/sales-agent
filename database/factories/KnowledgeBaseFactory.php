<?php

namespace Database\Factories;

use App\Domains\KnowledgeBase\Models\KnowledgeBase;
use Illuminate\Database\Eloquent\Factories\Factory;

class KnowledgeBaseFactory extends Factory
{
    protected $model = KnowledgeBase::class;

    public function definition(): array
    {
        $categories = ['product', 'pricing', 'shipping', 'faq', 'policy'];

        return [
            'category' => fake()->randomElement($categories),
            'title' => fake()->sentence(4),
            'content' => fake()->realText(200),
            'active' => true,
        ];
    }
}
