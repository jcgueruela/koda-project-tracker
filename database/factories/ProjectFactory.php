<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'client_name' => $this->faker->company(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(ProjectStatus::cases()),
            'priority' => $this->faker->randomElement(ProjectPriority::cases()),
            'start_date' => $this->faker->date(),
            'due_date' => $this->faker->date(),
        ];
    }
}
