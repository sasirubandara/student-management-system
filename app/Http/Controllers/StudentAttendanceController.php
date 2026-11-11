<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentAttendanceController extends Controller
{
    /**
     * Record or update attendance for students in a session.
     */
    public function save(
        Request $request,
        AttendanceSession $attendanceSession,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('update', $attendanceSession);

        $validated = $request->validate([
            'attendances' => [
                'required',
                'array',
                'min:1',
            ],

            'attendances.*.student_enrollment_id' => [
                'required',
                'integer',
                'distinct',
                'exists:student_enrollments,id',
            ],

            'attendances.*.status' => [
                'required',
                Rule::in([
                    StudentAttendance::STATUS_PRESENT,
                    StudentAttendance::STATUS_ABSENT,
                    StudentAttendance::STATUS_LATE,
                    StudentAttendance::STATUS_EXCUSED,
                ]),
            ],

            'attendances.*.remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $teachingAssignment = $attendanceSession->teachingAssignment;

        $enrollmentIds = collect($validated['attendances'])
            ->pluck('student_enrollment_id')
            ->all();

        /*
         * Retrieve only active enrollments belonging to the
         * academic class associated with this session.
         *
         * Archived or inactive students are excluded.
         */
        $enrollments = StudentEnrollment::query()
            ->whereIn('id', $enrollmentIds)
            ->where(
                'academic_class_id',
                $teachingAssignment->academic_class_id
            )
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereHas('student', function ($query): void {
                $query->where('status', Student::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->get()
            ->keyBy('id');

        if ($enrollments->count() !== count($enrollmentIds)) {
            throw ValidationException::withMessages([
                'attendances' =>
                    'One or more selected students are inactive, archived, '
                    . 'not actively enrolled, or do not belong to this academic class.',
            ]);
        }

        DB::transaction(function () use (
            $attendanceSession,
            $validated,
            $enrollments,
            $auditLogger
        ): void {
            foreach ($validated['attendances'] as $attendanceData) {
                $enrollment = $enrollments->get(
                    $attendanceData['student_enrollment_id']
                );

                $attendance = $attendanceSession
                    ->studentAttendances()
                    ->firstOrNew([
                        'student_enrollment_id' => $enrollment->id,
                    ]);

                $isNew = ! $attendance->exists;

                $previousStatus = $attendance->status;

                $attendance->fill([
                    'status' => $attendanceData['status'],
                    'remarks' => $attendanceData['remarks'] ?? null,
                ]);

                // Track only field names, never the actual remarks.
                $changedFields = $isNew
                    ? ['status']
                    : array_values(array_intersect(
                        ['status', 'remarks'],
                        array_keys($attendance->getDirty())
                    ));

                if (! $isNew && $changedFields === []) {
                    continue;
                }

                $attendanceSession->studentAttendances()
                    ->save($attendance);

                $properties = [
                    'changed_fields' => $changedFields,
                ];

                // Status changes are useful audit information.
                if (
                    ! $isNew
                    && in_array('status', $changedFields, true)
                ) {
                    $properties['changes'] = [
                        'status' => [
                            'old' => $previousStatus,
                            'new' => $attendance->status,
                        ],
                    ];
                }

                $auditLogger->record(
                    $isNew
                        ? 'attendance.record_created'
                        : 'attendance.record_updated',
                    $attendance,
                    $properties
                );
            }
        });

        return to_route('attendance-sessions.show', $attendanceSession)
            ->with('success', 'Student attendance saved successfully.');
    }

    /**
     * Remove an individual attendance record.
     */
    public function destroy(
        StudentAttendance $studentAttendance,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('delete', $studentAttendance);

        $attendanceSession = $studentAttendance->attendanceSession;

        DB::transaction(function () use (
            $studentAttendance,
            $auditLogger
        ): void {
            $studentAttendance->delete();

            $auditLogger->record(
                'attendance.record_deleted',
                $studentAttendance,
                [
                    'changed_fields' => ['deleted'],
                ]
            );
        });

        return to_route('attendance-sessions.show', $attendanceSession)
            ->with('success', 'Attendance record removed successfully.');
    }
}