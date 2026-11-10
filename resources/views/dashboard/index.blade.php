<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container students-page dashboard-page">

        <header class="page-header">
            <div>
                <h1>Dashboard</h1>

                <p>
                    Overview of students, teachers, academic activities,
                    attendance, and examinations.
                </p>
            </div>
        </header>

        {{-- Main statistics --}}

        <section class="dashboard-stats">

            <article class="dashboard-card">
                <div class="dashboard-card__icon">🎓</div>

                <h2>Students</h2>

                <p class="dashboard-card__value">
                    {{ number_format($studentStats['total']) }}
                </p>

                <p class="dashboard-card__description">
                    {{ number_format($studentStats['active']) }} active
                    ·
                    {{ number_format($studentStats['inactive']) }} inactive
                </p>

                @if ($isAdmin)
                    <p class="dashboard-card__description">
                        {{ number_format($studentStats['archived']) }} archived
                    </p>
                @endif

                <a href="{{ route('students.index') }}">
                    View Students
                </a>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__icon">👨‍🏫</div>

                <h2>Teachers</h2>

                <p class="dashboard-card__value">
                    {{ number_format($teacherStats['total']) }}
                </p>

                <p class="dashboard-card__description">
                    {{ number_format($teacherStats['active']) }} active
                    ·
                    {{ number_format($teacherStats['inactive']) }} inactive
                </p>

                @if ($isAdmin)
                    <p class="dashboard-card__description">
                        {{ number_format($teacherStats['archived']) }} archived
                    </p>
                @endif

                <a href="{{ route('teachers.index') }}">
                    View Teachers
                </a>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__icon">🏫</div>

                <h2>Academic Classes</h2>

                <p class="dashboard-card__value">
                    {{ number_format($classStats['active']) }}
                </p>

                <p class="dashboard-card__description">
                    Active classes out of {{ number_format($classStats['total']) }}
                </p>

                <a href="{{ route('academic-classes.index') }}">
                    View Classes
                </a>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__icon">📚</div>

                <h2>Subjects</h2>

                <p class="dashboard-card__value">
                    {{ number_format($subjectStats['active']) }}
                </p>

                <p class="dashboard-card__description">
                    Active subjects out of {{ number_format($subjectStats['total']) }}
                </p>

                <a href="{{ route('subjects.index') }}">
                    View Subjects
                </a>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__icon">📝</div>

                <h2>Student Enrollments</h2>

                <p class="dashboard-card__value">
                    {{ number_format($enrollmentStats['active']) }}
                </p>

                <p class="dashboard-card__description">
                    Active enrollments
                </p>

                <a href="{{ route('student-enrollments.index') }}">
                    View Enrollments
                </a>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__icon">👥</div>

                <h2>Teaching Assignments</h2>

                <p class="dashboard-card__value">
                    {{ number_format($totalTeachingAssignments) }}
                </p>

                <p class="dashboard-card__description">
                    Teacher-class-subject assignments
                </p>

                <a href="{{ route('teaching-assignments.index') }}">
                    View Assignments
                </a>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__icon">📋</div>

                <h2>Attendance Sessions</h2>

                <p class="dashboard-card__value">
                    {{ number_format($attendanceStats['sessions_this_month']) }}
                </p>

                <p class="dashboard-card__description">
                    Sessions this month
                </p>

                <a href="{{ route('attendance-sessions.index') }}">
                    View Attendance
                </a>
            </article>

            <article class="dashboard-card">
                <div class="dashboard-card__icon">📊</div>

                <h2>Examinations</h2>

                <p class="dashboard-card__value">
                    {{ number_format($examinationStats['total']) }}
                </p>

                <p class="dashboard-card__description">
                    {{ number_format($examinationStats['upcoming']) }} upcoming
                    ·
                    {{ number_format($examinationStats['results']) }} results
                </p>

                <a href="{{ route('examinations.index') }}">
                    View Examinations
                </a>
            </article>

        </section>

        {{-- Enrollment statistics --}}

        <section class="dashboard-section">

            <header class="dashboard-section__header">
                <h2>Enrollment Overview</h2>

                <p>
                    Current distribution of student enrollment statuses.
                </p>
            </header>

            <div class="dashboard-summary-grid">

                <article class="dashboard-summary-card">
                    <strong>Total Enrollments</strong>
                    <span>{{ number_format($enrollmentStats['total']) }}</span>
                </article>

                <article class="dashboard-summary-card">
                    <strong>Active</strong>
                    <span>{{ number_format($enrollmentStats['active']) }}</span>
                </article>

                <article class="dashboard-summary-card">
                    <strong>Completed</strong>
                    <span>{{ number_format($enrollmentStats['completed']) }}</span>
                </article>

                <article class="dashboard-summary-card">
                    <strong>Withdrawn</strong>
                    <span>{{ number_format($enrollmentStats['withdrawn']) }}</span>
                </article>

            </div>

        </section>

        {{-- Attendance statistics --}}

        <section class="dashboard-section">

            <header class="dashboard-section__header">
                <h2>Attendance Overview</h2>

                <p>
                    Recorded student attendance across all sessions.
                </p>
            </header>

            <div class="dashboard-summary-grid">

                <article class="dashboard-summary-card">
                    <strong>Total Sessions</strong>
                    <span>{{ number_format($attendanceStats['total_sessions']) }}</span>
                </article>

                <article class="dashboard-summary-card">
                    <strong>Present</strong>
                    <span>{{ number_format($attendanceStats['present']) }}</span>
                </article>

                <article class="dashboard-summary-card">
                    <strong>Absent</strong>
                    <span>{{ number_format($attendanceStats['absent']) }}</span>
                </article>

                <article class="dashboard-summary-card">
                    <strong>Late</strong>
                    <span>{{ number_format($attendanceStats['late']) }}</span>
                </article>

                <article class="dashboard-summary-card">
                    <strong>Excused</strong>
                    <span>{{ number_format($attendanceStats['excused']) }}</span>
                </article>

            </div>

        </section>

        {{-- Upcoming examinations --}}

        <section class="dashboard-section">

            <header class="dashboard-section__header">
                <h2>Upcoming Examinations</h2>

                <a href="{{ route('examinations.index') }}">
                    View All
                </a>
            </header>

            <div class="table-responsive">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Examination</th>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($upcomingExaminations as $examination)

                            <tr>
                                <td>
                                    <a href="{{ route('examinations.show', $examination) }}">
                                        {{ $examination->title }}
                                    </a>
                                </td>

                                <td>
                                    {{ $examination->teachingAssignment->teacher?->teacher_number ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $examination->teachingAssignment->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $examination->teachingAssignment->subject?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $examination->exam_date->format('d M Y') }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="5">
                                    No upcoming examinations.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

        {{-- Recent students --}}

        <section class="dashboard-section">

            <header class="dashboard-section__header">
                <h2>Recently Registered Students</h2>

                <a href="{{ route('students.index') }}">
                    View All
                </a>
            </header>

            <div class="table-responsive">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($recentStudents as $student)

                            <tr>
                                <td>
                                    {{ $student->student_number }}
                                </td>

                                <td>
                                    <a href="{{ route('students.show', $student) }}">
                                        {{ $student->first_name }}
                                        {{ $student->last_name }}
                                    </a>
                                </td>

                                <td>
                                    {{ $student->email ?: '—' }}
                                </td>

                                <td>
                                    {{ ucfirst($student->status) }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4">
                                    No student records available.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

        {{-- Recent enrollments --}}

        <section class="dashboard-section">

            <header class="dashboard-section__header">
                <h2>Recent Student Enrollments</h2>

                <a href="{{ route('student-enrollments.index') }}">
                    View All
                </a>
            </header>

            <div class="table-responsive">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Academic Class</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($recentEnrollments as $enrollment)

                            <tr>
                                <td>
                                    {{ $enrollment->student?->student_number ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $enrollment->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $enrollment->enrollment_date->format('d M Y') }}
                                </td>

                                <td>
                                    {{ ucfirst($enrollment->status) }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4">
                                    No enrollment records available.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

        {{-- Recent attendance sessions --}}

        <section class="dashboard-section">

            <header class="dashboard-section__header">
                <h2>Recent Attendance Sessions</h2>

                <a href="{{ route('attendance-sessions.index') }}">
                    View All
                </a>
            </header>

            <div class="table-responsive">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($recentAttendanceSessions as $session)

                            <tr>
                                <td>
                                    {{ $session->teachingAssignment->teacher?->teacher_number ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $session->teachingAssignment->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $session->teachingAssignment->subject?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    <a href="{{ route('attendance-sessions.show', $session) }}">
                                        {{ $session->attendance_date->format('d M Y') }}
                                    </a>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4">
                                    No attendance sessions available.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

    </main>

</body>
</html>