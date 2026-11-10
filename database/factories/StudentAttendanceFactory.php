<?php

namespace Database\Factories;

use App\Models\AttendanceSession;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentAttendance>
 */
class StudentAttendanceFactory extends Factory
{
    protected $model = StudentAttendance::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'attendance_session_id' => AttendanceSession::factory(),
            'status' => fake()->randomElement([
                StudentAttendance::STATUS_PRESENT,
                StudentAttendance::STATUS_ABSENT,
                StudentAttendance::STATUS_LATE,
                StudentAttendance::STATUS_EXCUSED,
            ]),
            'remarks' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Ensure the default enrollment belongs to the session's class.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (
            StudentAttendance $attendance
        ): void {
            if ($attendance->student_enrollment_id !== null) {
                return;
            }

            $session = AttendanceSession::query()
                ->with('teachingAssignment')
                ->findOrFail($attendance->attendance_session_id);

            $enrollment = StudentEnrollment::factory()->create([
                'academic_class_id' => $session->teachingAssignment->academic_class_id,
            ]);

            $attendance->student_enrollment_id = $enrollment->id;
        });
    }

    /**
     * Associate attendance with an existing session.
     */
    public function forSession(AttendanceSession $session): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_session_id' => $session->id,
        ]);
    }

    /**
     * Associate attendance with an existing enrollment.
     */
    public function forEnrollment(
        StudentEnrollment $enrollment
    ): static {
        return $this->state(fn (array $attributes) => [
            'student_enrollment_id' => $enrollment->id,
        ]);
    }
}