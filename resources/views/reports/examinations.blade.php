<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination Reports</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="reports-page">

        <header class="report-header">
            <div>
                <h1>Examination Reports</h1>
                <p>Review examination records and grade distributions.</p>
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

            <form action="{{ route('reports.examinations') }}"
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

                    <a href="{{ route('reports.examinations') }}">
                        Reset
                    </a>
                </div>

            </form>

        </section>

        <section class="report-section">

            <h2>Examination Summary</h2>

            <div class="report-stats-grid">

                <article class="report-stat-card">
                    <span>Total Examinations</span>
                    <strong>{{ $totalExaminations }}</strong>
                </article>

                <article class="report-stat-card">
                    <span>Total Recorded Results</span>
                    <strong>{{ $gradeSummary->sum() }}</strong>
                </article>

                @forelse ($gradeSummary as $grade => $total)

                    <article class="report-stat-card">
                        <span>Grade {{ $grade }}</span>
                        <strong>{{ $total }}</strong>
                    </article>

                @empty

                    <article class="report-stat-card">
                        <span>Grade Distribution</span>
                        <strong>No results</strong>
                    </article>

                @endforelse

            </div>

        </section>

        <section class="report-panel">

            <h2>Examination Records</h2>

            <div class="report-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Examination</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Maximum Marks</th>
                            <th>Pass Marks</th>
                            <th>Results</th>
                            <th>Details</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($examinations as $examination)

                            @php
                                $assignment = $examination->teachingAssignment;
                            @endphp

                            <tr>
                                <td>{{ $examination->title }}</td>

                                <td>
                                    {{ ucwords(str_replace('_', ' ', $examination->exam_type)) }}
                                </td>

                                <td>
                                    {{ $examination->exam_date?->format('Y-m-d') }}
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

                                <td>
                                    {{ number_format((float) $examination->maximum_marks, 2) }}
                                </td>

                                <td>
                                    {{ $examination->pass_marks !== null
                                        ? number_format((float) $examination->pass_marks, 2)
                                        : '—' }}
                                </td>

                                <td>{{ $examination->results_count }}</td>

                                <td>
                                    <a
                                        href="{{ route('examinations.show', $examination) }}"
                                        class="report-view-link"
                                    >
                                        View
                                    </a>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="10">
                                    No examinations match these filters.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="report-pagination">
                {{ $examinations->links() }}
            </div>

        </section>

    </main>

</body>
</html>