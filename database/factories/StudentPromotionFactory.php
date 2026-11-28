<?php

namespace Database\Factories;

use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentPromotion>
 */
class StudentPromotionFactory extends Factory
{
    protected $model = StudentPromotion::class;

    public function definition(): array
    {
        return [
            'student_id' => null,
            'source_enrollment_id' => null,
            'destination_enrollment_id' => null,
            'promotion_type' => StudentPromotion::TYPE_ADVANCED,
            'promotion_date' => now()->toDateString(),
            'notes' => null,
            'promoted_by' => User::factory(),
        ];
    }

    /**
     * Configure valid source and destination enrollment references.
     */
    public function forEnrollments(
        StudentEnrollment $source,
        StudentEnrollment $destination
    ): static {
        return $this->state(fn (): array => [
            'student_id' => $source->student_id,
            'source_enrollment_id' => $source->id,
            'destination_enrollment_id' => $destination->id,
        ]);
    }
}
