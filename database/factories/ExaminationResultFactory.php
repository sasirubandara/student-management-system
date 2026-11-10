<?php

namespace Database\Factories;

use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\StudentEnrollment;
use App\Services\GradeCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExaminationResultFactory extends Factory
{
    protected $model = ExaminationResult::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'examination_id' => Examination::factory(),

            'student_enrollment_id' => null,

            'marks_obtained' => '0.00',

            'grade' => 'F',

            'remarks' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Configure factory callbacks.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (
            ExaminationResult $result
        ): void {
            $examination = Examination::query()
                ->with('teachingAssignment')
                ->findOrFail($result->examination_id);

            if ($result->student_enrollment_id === null) {
                $enrollment = StudentEnrollment::factory()->create([
                    'academic_class_id' =>
                        $examination->teachingAssignment->academic_class_id,
                ]);

                $result->student_enrollment_id = $enrollment->id;
            }

            $result->grade = app(GradeCalculator::class)->calculate(
                $result->marks_obtained,
                $examination->maximum_marks
            );
        });
    }

    /**
     * Associate the result with an existing examination.
     */
    public function forExamination(Examination $examination): static
    {
        return $this->state([
            'examination_id' => $examination->id,
        ]);
    }

    /**
     * Associate the result with an existing student enrollment.
     */
    public function forEnrollment(StudentEnrollment $enrollment): static
    {
        return $this->state([
            'student_enrollment_id' => $enrollment->id,
        ]);
    }
}