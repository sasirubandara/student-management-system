<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_number' => fake()->unique()->bothify('TCH-######'),

            'first_name' => fake()->firstName(),

            'last_name' => fake()->lastName(),

            'email' => fake()->unique()->safeEmail(),

            'phone' => fake()->optional()->numerify('07########'),

            'date_of_birth' => fake()
                ->dateTimeBetween('-60 years', '-22 years')
                ->format('Y-m-d'),

            'employment_date' => fake()
                ->dateTimeBetween('-10 years', 'now')
                ->format('Y-m-d'),

            'status' => Teacher::STATUS_ACTIVE,
        ];
    }
}