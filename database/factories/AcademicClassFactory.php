<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicClass>
 */
class AcademicClassFactory extends Factory
{
    protected $model = AcademicClass::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gradeLevel = fake()->numberBetween(1, 13);

        $section = fake()->randomElement([
            'A',
            'B',
            'C',
            'D',
        ]);

        return [
            'name' => "Grade {$gradeLevel} - {$section}",

            'code' => fake()->unique()->bothify('CLS-####'),

            'grade_level' => $gradeLevel,

            'academic_year' => now()->year,

            'description' => fake()->optional()->sentence(),

            'status' => AcademicClass::STATUS_ACTIVE,
        ];
    }
}