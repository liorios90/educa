<?php

namespace Database\Factories;

use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->unique()->words(2, true),
            'route_name' => 'dashboard',
            'icon' => 'home',
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
            'visible_to_all' => false,
            'is_group' => false,
            'parent_id' => null,
        ];
    }

    public function visibleToAll(): static
    {
        return $this->state(fn (array $attributes): array => [
            'visible_to_all' => true,
        ]);
    }

    public function group(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_group' => true,
            'route_name' => 'navigation.hub',
        ]);
    }

    public function childOf(NavigationItem $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'is_group' => false,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
