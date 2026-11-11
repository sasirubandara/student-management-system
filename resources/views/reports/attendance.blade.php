<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="reports-page">

        <header class="report-header">
            <div>
                <h1>Attendance Reports</h1>
                <p>Review attendance records using the available filters.</p>
            </div>

            <div class="report-actions">

                <a href="{{ route('reports.index') }}" class="report-back-link">
                    All Reports
                </a>

                <button
                    type="button"
                    class="students-page__button"
                    onclick="window.print()"
                >
                    Print Current View
                </button>

            </div>
        </header>

        <section class="report-panel">

            <h2>Report Filters</h2>

            <form action="{{ route('reports.attendance') }}"
                  method="GET"
                  class="report-filter-form">

                <div>
                    <label for="academic_class_id">Academic Class</label>

                    <select name="academic_class_id" id="academic_class_id">
                        <option value="">All Classes</option>

                        @foreach ($classes as $class)
                            <option
                                value="{{ $class->id }}"
                                @selected((string) ($filters['academic_class_id'] ?? '') === (string) $class->id)
                            >
                                {{ $class->name }} ({{ $class->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subject_id">Subject</label>

                    <select name="subject_id" id="subject_id">
                        <option value="">All Subjects</option>

                        @foreach ($subjects as $subject)
                            <option
                                value="{{ $subject->id }}"
                                @selected((string) ($filters['subject_id'] ?? '') === (string) $subject->id)
                            >
                                {{ $subject->name }} ({{ $subject->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_date">From Date</label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        value="{{ $filters['start_date'] ?? '' }}"
                    >
                </div>

                <div>
                    <label for="end_date">To Date</label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        value="{{ $filters['end_date'] ?? '' }}"
                    >
                </div>

                <div class="report-filter-actions">
                    <button type="submit" class="students-page__button">
                        Apply Filters
                    </button>

                    <a href="{{ route('reports.attendance') }}">
                        Reset
                    </a>
                </div>

            </form>

        </section>

        <section class="report-section">

            <h2>Attendance Summary</h2>

            <p class="report-description">
                {{ $totalSessions }} attendance sessions match the selected filters.
            </p>

            <div class="report-stats-grid">

                <article class="report-stat-card">
                    <span>Total Attendance Records</span>
                    <strong>{{ $attendanceSummary->sum() }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Present</span>
                    <strong>{{ $attendanceSummary->get('present', 0) }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Absent</span>
                    <strong>{{ $attendanceSummary->get('absent', 0) }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Late</span>
                    <strong>{{ $attendanceSummary->get('late', 0) }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Excused</span>
                    <strong>{{ $attendanceSummary->get('excused', 0) }}</strong>
                </article>

            </div>

        </section>

        <section class="report-panel">

            <h2>Attendance Session Records</h2>

            <div class="report-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Total</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Excused</th>
                            <th>Details</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($sessions as $session)

                            @php
                                $assignment = $session->teachingAssignment;
                            @endphp

                            <tr>
                                <td>
                                    {{ $session->attendance_date?->format('Y-m-d') }}
                                </td>

                                <td>
                                    {{ $assignment?->academicClass?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $assignment?->subject?->name ?? 'Unavailable' }}
                                </td>

                                <td>
                                    @if ($assignment?->teacher)
                                        {{ $assignment->teacher->first_name }}
                                        {{ $assignment->teacher->last_name }}
                                    @else
                                        Unavailable
                                    @endif
                                </td>

                                <td>{{ $session->student_attendances_count }}</td>
                                <td>{{ $session->present_count }}</td>
                                <td>{{ $session->absent_count }}</td>
                                <td>{{ $session->late_count }}</td>
                                <td>{{ $session->excused_count }}</td>

                                <td>
                                    <a
                                        href="{{ route('attendance-sessions.show', $session) }}"
                                        class="report-view-link"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="10">
                                    No attendance sessions match these filters.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="report-pagination">
                {{ $sessions->links() }}
            </div>

        </section>

    </main>

</body>
</html>