<?php

namespace Database\Factories;

use App\Models\AcademicClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeachingAssignment>
 */
class TeachingAssignmentFactory extends Factory
{
    protected $model = TeachingAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),

            'academic_class_id' => AcademicClass::factory(),

            'subject_id' => Subject::factory(),
        ];
    }

    /**
     * Configure factory callbacks.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (
            TeachingAssignment $assignment
        ): void {
            // Ensure the assigned subject belongs to the selected class.
            $assignment->academicClass
                ->subjects()
                ->syncWithoutDetaching([$assignment->subject_id]);
        });
    }
}