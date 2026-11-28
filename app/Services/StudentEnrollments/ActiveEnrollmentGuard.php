<?php

namespace App\Services\StudentEnrollments;

use App\Models\StudentEnrollment;
use Illuminate\Validation\ValidationException;

class ActiveEnrollmentGuard
{
    /**
     * Reject a second active enrollment for a student.
     *
     * Call only after locking the student row in a transaction.
     */
    public function assertCanActivate(
        int $studentId,
        ?int $exceptEnrollmentId = null
    ): void {
        $exists = StudentEnrollment::query()
            ->where('student_id', $studentId)
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->when(
                $exceptEnrollmentId !== null,
                fn ($query) => $query->whereKeyNot(
                    $exceptEnrollmentId
                )
            )
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'status' => 'This student already has an active enrollment.',
            ]);
        }
    }
}
