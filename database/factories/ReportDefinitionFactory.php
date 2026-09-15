<?php

namespace Database\Factories;

use App\Models\ReportDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportDefinition>
 */
class ReportDefinitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'source' => 'jornadas',
            'is_active' => true,
            'visible_to_all' => false,
        ];
    }

    public function visibleToAll(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visible_to_all' => true,
        ]);
    }
}
