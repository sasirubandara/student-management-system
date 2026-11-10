<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Attendance Session | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">

            <div>
                <h1>Attendance Session</h1>
                <p>
                    {{ $attendanceSession->attendance_date->format('d F Y') }}
                </p>
            </div>

            <div class="student-profile__actions">

                <a href="{{ route('attendance-sessions.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Attendance
                </a>

                @can('delete', $attendanceSession)

                    @if ($attendanceSession->studentAttendances->isEmpty())

                        <form
                            action="{{ route('attendance-sessions.destroy', $attendanceSession) }}"
                            method="POST"
                            onsubmit="return confirm('Are you sure you want to remove this attendance session?');"
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="students-page__button student-archive-button">
                                Remove Session
                            </button>
                        </form>

                    @endif

                @endcan

            </div>

        </header>

        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="error-summary" role="alert">

                <strong>Please correct the following errors:</strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif

        <!-- Session information -->
        <section class="student-profile">

            <div class="student-profile__section">

                <h3>Session Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Attendance Date</span>
                        <strong>
                            {{ $attendanceSession->attendance_date->format('d F Y') }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Teacher</span>
                        <strong>
                            {{ $attendanceSession->teachingAssignment->teacher->first_name }}
                            {{ $attendanceSession->teachingAssignment->teacher->last_name }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Teacher Number</span>
                        <strong>
                            {{ $attendanceSession->teachingAssignment->teacher->teacher_number }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Academic Class</span>
                        <strong>
                            {{ $attendanceSession->teachingAssignment->academicClass->name }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Subject</span>
                        <strong>
                            {{ $attendanceSession->teachingAssignment->subject->name }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Created By</span>
                        <strong>
                            {{ $attendanceSession->creator->name }}
                        </strong>
                    </div>

                </div>

            </div>

        </section>

        <!-- Attendance summary -->
        @php
            $attendanceByEnrollment = $attendanceSession->studentAttendances
                ->keyBy('student_enrollment_id');

            $eligibleEnrollmentIds = $eligibleEnrollments->pluck('id');

            $historicalAttendances = $attendanceSession->studentAttendances
                ->reject(
                    fn ($attendance) => $eligibleEnrollmentIds->contains(
                        $attendance->student_enrollment_id
                    )
                );

            $presentCount = $attendanceSession->studentAttendances
                ->where('status', \App\Models\StudentAttendance::STATUS_PRESENT)
                ->count();

            $absentCount = $attendanceSession->studentAttendances
                ->where('status', \App\Models\StudentAttendance::STATUS_ABSENT)
                ->count();

            $lateCount = $attendanceSession->studentAttendances
                ->where('status', \App\Models\StudentAttendance::STATUS_LATE)
                ->count();

            $excusedCount = $attendanceSession->studentAttendances
                ->where('status', \App\Models\StudentAttendance::STATUS_EXCUSED)
                ->count();
        @endphp

        <section class="students-page__summary">

            <h3>Attendance Summary</h3>

            <div class="student-profile__grid">

                <div class="student-profile__field">
                    <span>Present</span>
                    <strong>{{ $presentCount }}</strong>
                </div>

                <div class="student-profile__field">
                    <span>Absent</span>
                    <strong>{{ $absentCount }}</strong>
                </div>

                <div class="student-profile__field">
                    <span>Late</span>
                    <strong>{{ $lateCount }}</strong>
                </div>

                <div class="student-profile__field">
                    <span>Excused</span>
                    <strong>{{ $excusedCount }}</strong>
                </div>

                <div class="student-profile__field">
                    <span>Total Recorded</span>
                    <strong>
                        {{ $attendanceSession->studentAttendances->count() }}
                    </strong>
                </div>

            </div>

        </section>

        <!-- Student attendance form -->
        <section class="student-profile__section">

            <header class="students-page__header">
                <div>
                    <h2>Student Attendance</h2>
                    <p>
                        Eligible students:
                        {{ $eligibleEnrollments->count() }}
                    </p>
                </div>
            </header>

            @if ($eligibleEnrollments->isEmpty())

                <section class="students-page__empty">
                    <h3>No Eligible Students</h3>
                    <p>
                        There are currently no active student enrollments
                        for this academic class.
                    </p>
                </section>

            @else

                @can('update', $attendanceSession)

                    <form
                        action="{{ route('attendance-sessions.student-attendances.save', $attendanceSession) }}"
                        method="POST"
                    >

                        @csrf
                        @method('PUT')

                        <div class="students-page__table-wrapper">

                            <table class="students-table">

                                <thead>
                                    <tr>
                                        <th>Student Number</th>
                                        <th>Student Name</th>
                                        <th>Attendance Status</th>
                                        <th>Remarks</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($eligibleEnrollments as $index => $enrollment)

                                        @php
                                            $existingAttendance = $attendanceByEnrollment->get($enrollment->id);

                                            $selectedStatus = old(
                                                "attendances.{$index}.status",
                                                $existingAttendance?->status ?? 'present'
                                            );

                                            $selectedRemarks = old(
                                                "attendances.{$index}.remarks",
                                                $existingAttendance?->remarks ?? ''
                                            );
                                        @endphp

                                        <tr>

                                            <td>
                                                <strong>
                                                    {{ $enrollment->student->student_number }}
                                                </strong>

                                                <input
                                                    type="hidden"
                                                    name="attendances[{{ $index }}][student_enrollment_id]"
                                                    value="{{ $enrollment->id }}"
                                                >
                                            </td>

                                            <td>
                                                {{ $enrollment->student->first_name }}
                                                {{ $enrollment->student->last_name }}
                                            </td>

                                            <td>

                                                <select
                                                    name="attendances[{{ $index }}][status]"
                                                    required
                                                >

                                                    <option
                                                        value="present"
                                                        @selected($selectedStatus === 'present')
                                                    >
                                                        Present
                                                    </option>

                                                    <option
                                                        value="absent"
                                                        @selected($selectedStatus === 'absent')
                                                    >
                                                        Absent
                                                    </option>

                                                    <option
                                                        value="late"
                                                        @selected($selectedStatus === 'late')
                                                    >
                                                        Late
                                                    </option>

                                                    <option
                                                        value="excused"
                                                        @selected($selectedStatus === 'excused')
                                                    >
                                                        Excused
                                                    </option>

                                                </select>

                                            </td>

                                            <td>

                                                <input
                                                    type="text"
                                                    name="attendances[{{ $index }}][remarks]"
                                                    value="{{ $selectedRemarks }}"
                                                    maxlength="2000"
                                                    placeholder="Optional remarks"
                                                >

                                            </td>

                                            <td>

                                                @if ($existingAttendance)

                                                    @can('delete', $existingAttendance)

                                                        <button
                                                            type="button"
                                                            class="students-table__view"
                                                            onclick="document.getElementById('delete-attendance-{{ $existingAttendance->id }}').submit();"
                                                        >
                                                            Remove Record
                                                        </button>

                                                    @endcan

                                                @else

                                                    <span>Not recorded</span>

                                                @endif

                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                        <div class="form-actions">

                            <button type="submit">
                                Save Attendance
                            </button>

                        </div>

                    </form>

                @endcan

                @cannot('update', $attendanceSession)

                    <p>Attendance editing is not available to your account.</p>

                @endcannot

            @endif

        </section>

        <!-- Historical attendance records -->
        @if ($historicalAttendances->isNotEmpty())

            <section class="student-profile__section">

                <h3>Historical Attendance Records</h3>

                <p>
                    These records are retained for historical reference because
                    the associated enrollment or student is no longer active.
                </p>

                <div class="students-page__table-wrapper">

                    <table class="students-table">

                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Student Name</th>
                                <th>Recorded Status</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($historicalAttendances as $attendance)

                                <tr>

                                    <td>
                                        {{ $attendance->studentEnrollment?->student?->student_number ?? 'Unavailable' }}
                                    </td>

                                    <td>
                                        {{ $attendance->studentEnrollment?->student?->first_name ?? '' }}
                                        {{ $attendance->studentEnrollment?->student?->last_name ?? '' }}
                                    </td>

                                    <td>
                                        {{ ucfirst($attendance->status) }}
                                    </td>

                                    <td>
                                        {{ $attendance->remarks ?? '—' }}
                                    </td>

                                    <td>

                                        @can('delete', $attendance)

                                            <form
                                                id="delete-attendance-{{ $attendance->id }}"
                                                action="{{ route('student-attendances.destroy', $attendance) }}"
                                                method="POST"
                                                onsubmit="return confirm('Remove this attendance record?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit">
                                                    Remove Record
                                                </button>

                                            </form>

                                        @endcan

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </section>

        @endif

        {{-- Hidden individual attendance deletion forms --}}
        @foreach ($eligibleEnrollments as $enrollment)

            @php
                $existingAttendance = $attendanceByEnrollment->get($enrollment->id);
            @endphp

            @if ($existingAttendance)

                <form
                    id="delete-attendance-{{ $existingAttendance->id }}"
                    action="{{ route('student-attendances.destroy', $existingAttendance) }}"
                    method="POST"
                    onsubmit="return confirm('Remove this attendance record?');"
                    hidden
                >
                    @csrf
                    @method('DELETE')
                </form>

            @endif

        @endforeach

    </main>

</body>
</html>