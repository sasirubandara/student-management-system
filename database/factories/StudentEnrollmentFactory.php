<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnrollment>
 */
class StudentEnrollmentFactory extends Factory
{
    protected $model = StudentEnrollment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),

            'academic_class_id' => AcademicClass::factory(),

            'enrollment_date' => now()->toDateString(),

            'status' => StudentEnrollment::STATUS_ACTIVE,

            'notes' => fake()->optional()->sentence(),
        ];
    }
}