<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_number' => fake()->unique()->bothify('STU-######'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('07########'),
            'date_of_birth' => fake()
                ->dateTimeBetween('-18 years', '-5 years')
                ->format('Y-m-d'),
            'enrollment_date' => fake()->date(),
            'status' => 'active',
        ];
    }
}