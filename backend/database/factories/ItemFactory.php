<?php

namespace Database\Factories;

use App\Enums\ItemKind;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->sentence(3),
            'kind' => ItemKind::Todo,
            'occurs_at' => null,
            'position' => 0,
            'completed_at' => null,
        ];
    }

    public function meeting(): static
    {
        return $this->state(fn () => [
            'kind' => ItemKind::Meeting,
            'occurs_at' => now()->addDay()->setTime(10, 0),
        ]);
    }

    public function task(): static
    {
        return $this->state(fn () => [
            'kind' => ItemKind::Task,
            'occurs_at' => now()->addDay()->setTime(16, 0),
        ]);
    }
}
