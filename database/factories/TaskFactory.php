<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status'      => fake()->randomElement(['pending', 'in_progress', 'done']),
            'assignee'    => fake()->name(),
            'due_date'    => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }
}
