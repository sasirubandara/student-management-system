<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSession>
 */
class AttendanceSessionFactory extends Factory
{
    protected $model = AttendanceSession::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'teaching_assignment_id' => TeachingAssignment::factory(),
            'attendance_date' => fake()->date(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Associate the session with an existing teaching assignment.
     */
    public function forTeachingAssignment(
        TeachingAssignment $teachingAssignment
    ): static {
        return $this->state(fn (array $attributes) => [
            'teaching_assignment_id' => $teachingAssignment->id,
        ]);
    }
}