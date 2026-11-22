<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Examination Report</title>
    <style>
        @page { margin: 25px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 13px; margin-top: 20px; }

        .metadata { color: #555; margin-bottom: 15px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            word-wrap: break-word;
        }

        th { background: #eee; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>{{ $institutionName }}</h1>
    <h2>Examination Report</h2>

    <div class="metadata">
        Generated: {{ $generatedAt }}
    </div>

    <h2>Applied Filters</h2>
    <p>
        Class ID: {{ $filters['academic_class_id'] ?? 'All' }}
        |
        Subject ID: {{ $filters['subject_id'] ?? 'All' }}
    </p>
    <p>
        From: {{ $filters['start_date'] ?? 'Any' }}
        |
        To: {{ $filters['end_date'] ?? 'Any' }}
    </p>

    <h2>Examination Summary</h2>

    <p>Total examinations: {{ $totalExaminations }}</p>
    <p>Total recorded results: {{ $gradeSummary->sum() }}</p>

    <table>
        <thead>
            <tr>
                <th>Grade</th>
                <th>Number of Results</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gradeSummary as $grade => $total)
                <tr>
                    <td>{{ $grade }}</td>
                    <td>{{ $total }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2">No examination results available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Examination Records</h2>

    <table>
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
            </tr>
        </thead>

        <tbody>
            @forelse ($examinations as $examination)
                @php
                    $assignment = $examination->teachingAssignment;
                    $teacher = $assignment?->teacher;
                @endphp

                <tr>
                    <td>{{ $examination->title }}</td>
                    <td>
                        {{ ucwords(str_replace('_', ' ', $examination->exam_type)) }}
                    </td>
                    <td>{{ $examination->exam_date?->format('Y-m-d') }}</td>
                    <td>{{ $assignment?->academicClass?->name ?? 'Unavailable' }}</td>
                    <td>{{ $assignment?->subject?->name ?? 'Unavailable' }}</td>
                    <td>
                        {{ $teacher
                            ? $teacher->first_name.' '.$teacher->last_name
                            : 'Unavailable' }}
                    </td>
                    <td>{{ number_format((float) $examination->maximum_marks, 2) }}</td>
                    <td>
                        {{ $examination->pass_marks !== null
                            ? number_format((float) $examination->pass_marks, 2)
                            : '—' }}
                    </td>
                    <td>{{ $examination->results_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        No examinations match these filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>