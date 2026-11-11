<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Report - {{ $student->student_number }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="reports-page">

        <header class="report-header">

            <div>
                <h1>Student Academic Report</h1>

                <p>
                    Academic history and performance overview.
                </p>
            </div>

            <div class="report-actions">

                <a
                    href="{{ route('reports.students.index') }}"
                    class="report-back-link"
                >
                    Back to Students
                </a>

                <button
                    type="button"
                    class="students-page__button"
                    onclick="window.print()"
                >
                    Print Report
                </button>

            </div>

        </header>

        {{-- Student information --}}

        <section class="report-panel">

            <h2>Student Information</h2>

            <div class="report-student-details">

                <div>
                    <span>Student Number</span>
                    <strong>{{ $student->student_number }}</strong>
                </div>

                <div>
                    <span>Student Name</span>
                    <strong>
                        {{ $student->first_name }}
                        {{ $student->last_name }}
                    </strong>
                </div>

                <div>
                    <span>Email</span>
                    <strong>{{ $student->email ?? '—' }}</strong>
                </div>

                <div>
                    <span>Phone</span>
                    <strong>{{ $student->phone ?? '—' }}</strong>
                </div>

                <div>
                    <span>Student Status</span>
                    <strong>{{ ucfirst($student->status) }}</strong>
                </div>

                <div>
                    <span>Registration Date</span>
                    <strong>
                        {{ $student->enrollment_date ?? '—' }}
                    </strong>
                </div>

                @if ($student->trashed())
                    <div>
                        <span>Record Status</span>
                        <strong>Archived</strong>
                    </div>
                @endif

            </div>

        </section>

        {{-- Enrollment summary --}}

        <section class="report-section">

            <h2>Enrollment Summary</h2>

            <div class="report-stats-grid">

                <article class="report-stat-card">
                    <span>Total Enrollments</span>
                    <strong>{{ $enrollmentStats['total'] }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Active</span>
                    <strong>{{ $enrollmentStats['active'] }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Completed</span>
                    <strong>{{ $enrollmentStats['completed'] }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Withdrawn</span>
                    <strong>{{ $enrollmentStats['withdrawn'] }}</strong>
                </article>

            </div>

        </section>

        {{-- Enrollment history --}}

        <section class="report-panel">

            <h2>Enrollment History</h2>

            <div class="report-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Academic Class</th>
                            <th>Class Code</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($enrollments as $enrollment)

                            <tr>
                                <td>
                                    {{ $enrollment->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $enrollment->academicClass?->code ?? '—' }}
                                </td>

                                <td>
                                    {{ $enrollment->enrollment_date?->format('Y-m-d') ?? '—' }}
                                </td>

                                <td>
                                    {{ ucfirst($enrollment->status) }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4">
                                    No enrollment history found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

        {{-- Attendance summary --}}

        <section class="report-section">

            <h2>Attendance Summary</h2>

            <div class="report-stats-grid">

                <article class="report-stat-card">
                    <span>Total Records</span>
                    <strong>{{ $attendanceSummary->sum() }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Present</span>
                    <strong>
                        {{ $attendanceSummary->get('present', 0) }}
                    </strong>
                </article>

                <article class="report-stat-card">
                    <span>Absent</span>
                    <strong>
                        {{ $attendanceSummary->get('absent', 0) }}
                    </strong>
                </article>

                <article class="report-stat-card">
                    <span>Late</span>
                    <strong>
                        {{ $attendanceSummary->get('late', 0) }}
                    </strong>
                </article>

                <article class="report-stat-card">
                    <span>Excused</span>
                    <strong>
                        {{ $attendanceSummary->get('excused', 0) }}
                    </strong>
                </article>

            </div>

        </section>

        {{-- Attendance history --}}

        <section class="report-panel">

            <h2>Attendance History</h2>

            <div class="report-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Academic Class</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($attendanceRecords as $attendance)

                            <tr>
                                <td>
                                    {{ $attendance->attendanceSession?->attendance_date?->format('Y-m-d') ?? '—' }}
                                </td>

                                <td>
                                    {{ $attendance->attendanceSession?->teachingAssignment?->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $attendance->attendanceSession?->teachingAssignment?->subject?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    @php
                                        $teacher = $attendance->attendanceSession?->teachingAssignment?->teacher;
                                    @endphp

                                    {{ $teacher
                                        ? $teacher->first_name . ' ' . $teacher->last_name
                                        : 'Unavailable' }}
                                </td>

                                <td>
                                    {{ ucfirst($attendance->status) }}
                                </td>

                                <td>
                                    {{ $attendance->remarks ?? '—' }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="6">
                                    No attendance records found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="report-pagination">
                {{ $attendanceRecords->links() }}
            </div>

        </section>

        {{-- Examination and grade summary --}}

        <section class="report-section">

            <h2>Grade Summary</h2>

            <div class="report-stats-grid">

                <article class="report-stat-card">
                    <span>Total Results</span>
                    <strong>{{ $gradeSummary->sum() }}</strong>
                </article>

                @forelse ($gradeSummary as $grade => $total)

                    <article class="report-stat-card">
                        <span>Grade {{ $grade }}</span>
                        <strong>{{ $total }}</strong>
                    </article>

                @empty

                    <article class="report-stat-card">
                        <span>Grades</span>
                        <strong>No results</strong>
                    </article>

                @endforelse

            </div>

        </section>

        {{-- Examination results --}}

        <section class="report-panel">

            <h2>Examination Results</h2>

            <div class="report-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Examination</th>
                            <th>Date</th>
                            <th>Academic Class</th>
                            <th>Subject</th>
                            <th>Marks</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($examinationResults as $result)

                            <tr>

                                <td>
                                    {{ $result->examination?->title ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $result->examination?->exam_date?->format('Y-m-d') ?? '—' }}
                                </td>

                                <td>
                                    {{ $result->examination?->teachingAssignment?->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $result->examination?->teachingAssignment?->subject?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    @if ($result->examination)
                                        {{ number_format((float) $result->marks_obtained, 2) }}
                                        /
                                        {{ number_format((float) $result->examination->maximum_marks, 2) }}
                                    @else
                                        {{ number_format((float) $result->marks_obtained, 2) }}
                                    @endif
                                </td>

                                <td>
                                    {{ $result->grade }}
                                </td>

                                <td>
                                    {{ $result->remarks ?? '—' }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="7">
                                    No examination results found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="report-pagination">
                {{ $examinationResults->links() }}
            </div>

        </section>

    </main>

</body>
</html>