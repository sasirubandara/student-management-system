<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Mathematics',
                'Science',
                'English',
                'History',
                'Geography',
                'ICT',
                'Commerce',
                'Art',
                'Music',
            ]),

            'code' => fake()->unique()->bothify('SUB-####'),

            'description' => fake()->optional()->sentence(),

            'status' => Subject::STATUS_ACTIVE,
        ];
    }
}