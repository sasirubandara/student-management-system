<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
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
        AttendanceSession $attendanceSession
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
            $enrollments
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

                if ($attendance->exists) {
                    Gate::authorize('update', $attendance);
                } else {
                    Gate::authorize('create', StudentAttendance::class);
                }

                $attendance->fill([
                    'status' => $attendanceData['status'],
                    'remarks' => $attendanceData['remarks'] ?? null,
                ]);

                $attendanceSession->studentAttendances()
                    ->save($attendance);
            }
        });

        return to_route('attendance-sessions.show', $attendanceSession)
            ->with('success', 'Student attendance saved successfully.');
    }

    /**
     * Remove an individual attendance record.
     */
    public function destroy(
        StudentAttendance $studentAttendance
    ): RedirectResponse {
        Gate::authorize('delete', $studentAttendance);

        $attendanceSession = $studentAttendance->attendanceSession;

        $studentAttendance->delete();

        return to_route('attendance-sessions.show', $attendanceSession)
            ->with('success', 'Attendance record removed successfully.');
    }
}