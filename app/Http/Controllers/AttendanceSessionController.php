<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use App\Models\AttendanceSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceSessionController extends Controller
{
    /**
     * Display attendance sessions with optional search.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AttendanceSession::class);

        $search = trim((string) $request->query('search', ''));

        $attendanceSessions = AttendanceSession::query()
            ->with([
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
                'creator',
            ])
            ->withCount('studentAttendances')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = "%{$search}%";

                $query->where(function (Builder $query) use ($term): void {
                    $query->whereHas(
                        'teachingAssignment.teacher',
                        function (Builder $teacherQuery) use ($term): void {
                            $teacherQuery->where('teacher_number', 'like', $term)
                                ->orWhere('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term);
                        }
                    )
                        ->orWhereHas(
                            'teachingAssignment.academicClass',
                            function (Builder $classQuery) use ($term): void {
                                $classQuery->where('name', 'like', $term)
                                    ->orWhere('code', 'like', $term);
                            }
                        )
                        ->orWhereHas(
                            'teachingAssignment.subject',
                            function (Builder $subjectQuery) use ($term): void {
                                $subjectQuery->where('name', 'like', $term)
                                    ->orWhere('code', 'like', $term);
                            }
                        )
                        ->orWhereDate('attendance_date', 'like', $term);
                });
            })
            ->latest('attendance_date')
            ->paginate(10)
            ->withQueryString();

        return view('attendance-sessions.index', compact(
            'attendanceSessions',
            'search'
        ));
    }

    /**
     * Display the attendance session creation form.
     */
    public function create(): View
    {
        Gate::authorize('create', AttendanceSession::class);

        $teachingAssignments = TeachingAssignment::query()
            ->whereHas('teacher', function (Builder $query): void {
                $query->where('status', Teacher::STATUS_ACTIVE);
            })
            ->whereHas('academicClass', function (Builder $query): void {
                $query->where('status', AcademicClass::STATUS_ACTIVE);
            })
            ->whereHas('subject', function (Builder $query): void {
                $query->where('status', Subject::STATUS_ACTIVE);
            })
            ->with([
                'teacher',
                'academicClass',
                'subject',
            ])
            ->latest()
            ->get();

        return view('attendance-sessions.create', compact(
            'teachingAssignments'
        ));
    }

    /**
     * Store a new attendance session.
     */
    public function store(
        Request $request,
        AuditLogger $auditLogger,
        NotificationService $notificationService
    ): RedirectResponse {
        Gate::authorize('create', AttendanceSession::class);

        $validated = $request->validate([
            'teaching_assignment_id' => [
                'required',
                'integer',
                'exists:teaching_assignments,id',
            ],

            'attendance_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ]);

        $duplicateSessionExists = AttendanceSession::query()
            ->where(
                'teaching_assignment_id',
                $validated['teaching_assignment_id']
            )
            ->whereDate(
                'attendance_date',
                $validated['attendance_date']
            )
            ->exists();

        if ($duplicateSessionExists) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => 'An attendance session already exists for this teaching assignment and date.',
            ]);
        }

        $teachingAssignment = TeachingAssignment::query()
            ->whereKey($validated['teaching_assignment_id'])
            ->whereHas('teacher', function (Builder $query): void {
                $query->where('status', Teacher::STATUS_ACTIVE);
            })
            ->whereHas('academicClass', function (Builder $query): void {
                $query->where('status', AcademicClass::STATUS_ACTIVE);
            })
            ->whereHas('subject', function (Builder $query): void {
                $query->where('status', Subject::STATUS_ACTIVE);
            })
            ->exists();

        if (! $teachingAssignment) {
            throw ValidationException::withMessages([
                'teaching_assignment_id' => 'The selected teaching assignment is unavailable. '
                    .'Select an active teacher, academic class and subject.',
            ]);
        }

        $actor = $request->user();

        $attendanceSession = DB::transaction(function () use (
            $validated,
            $actor,
            $auditLogger,
            $notificationService
        ): AttendanceSession {
            $attendanceSession = AttendanceSession::create([
                'teaching_assignment_id' => $validated['teaching_assignment_id'],
                'attendance_date' => $validated['attendance_date'],
                'created_by' => $actor->id,
            ]);

            // Record attendance session creation in the audit log.
            $auditLogger->record(
                'attendance.session_created',
                $attendanceSession,
                [
                    'changed_fields' => [
                        'teaching_assignment_id',
                        'attendance_date',
                        'created_by',
                    ],
                ]
            );

            // Load related records for the notification message.
            $attendanceSession->load([
                'teachingAssignment.teacher',
                'teachingAssignment.academicClass',
                'teachingAssignment.subject',
            ]);

            $assignment = $attendanceSession->teachingAssignment;
            $teacher = $assignment->teacher;
            $academicClass = $assignment->academicClass;
            $subject = $assignment->subject;

            // Notify other active users.
            $notificationService->notifyOtherActiveUsers(
                actor: $actor,
                event: 'attendance.session_created',
                title: 'New attendance session created',
                message: 'An attendance session for '
                    .$academicClass->name
                    .' — '
                    .$subject->name
                    .' with '
                    .$teacher->first_name
                    .' '
                    .$teacher->last_name
                    .' has been created.',
                url: route('attendance-sessions.show', $attendanceSession),
                icon: 'calendar-check',
            );

            return $attendanceSession;
        });

        return to_route('attendance-sessions.show', $attendanceSession)
            ->with('success', 'Attendance session created successfully.');
    }

    /**
     * Display an individual attendance session.
     */
    public function show(AttendanceSession $attendanceSession): View
    {
        Gate::authorize('view', $attendanceSession);

        $attendanceSession->load([
            'teachingAssignment.teacher',
            'teachingAssignment.academicClass',
            'teachingAssignment.subject',
            'creator',
            'studentAttendances.studentEnrollment.student',
        ]);

        /*
         * Retrieve active enrollments belonging to the class
         * associated with this teaching assignment.
         */
        $eligibleEnrollments = StudentEnrollment::query()
            ->where(
                'academic_class_id',
                $attendanceSession->teachingAssignment->academic_class_id
            )
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->whereHas('student', function (Builder $query): void {
                $query->where('status', Student::STATUS_ACTIVE)
                    ->whereNull('deleted_at');
            })
            ->with('student')
            ->orderBy('id')
            ->get();

        return view('attendance-sessions.show', compact(
            'attendanceSession',
            'eligibleEnrollments'
        ));
    }

    /**
     * Remove an attendance session that has no attendance records.
     */
    public function destroy(
        AttendanceSession $attendanceSession,
        AuditLogger $auditLogger
    ): RedirectResponse {
        Gate::authorize('delete', $attendanceSession);

        $deleted = DB::transaction(function () use (
            $attendanceSession,
            $auditLogger
        ): bool {
            $lockedSession = AttendanceSession::query()
                ->whereKey($attendanceSession->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->studentAttendances()->exists()) {
                return false;
            }

            $lockedSession->delete();

            $auditLogger->record(
                'attendance.session_deleted',
                $lockedSession,
                [
                    'changed_fields' => ['deleted'],
                ]
            );

            return true;
        });

        if (! $deleted) {
            return to_route('attendance-sessions.show', $attendanceSession)
                ->withErrors([
                    'attendance_session' => 'This session cannot be removed because it contains '
                        .'student attendance records. Attendance history must be preserved.',
                ]);
        }

        return to_route('attendance-sessions.index')
            ->with('success', 'Attendance session removed successfully.');
    }
}
