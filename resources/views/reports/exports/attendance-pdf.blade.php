<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>

    <style>
        @page {
            margin: 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
        }

        h1 {
            margin-bottom: 5px;
            font-size: 18px;
        }

        h2 {
            margin-top: 20px;
            font-size: 13px;
        }

        .metadata {
            color: #555;
            margin-bottom: 15px;
        }

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

        th {
            background: #eee;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <h1>{{ $institutionName }}</h1>
    <h2>Attendance Report</h2>

    <div class="metadata">
        Generated: {{ $generatedAt }}
    </div>

    <h2>Applied Filters</h2>

    <p>
        Class: {{ $filterLabels['academic_class'] }}
        |
        Subject: {{ $filterLabels['subject'] }}
    </p>

    <p>
        From: {{ $filterLabels['start_date'] }}
        |
        To: {{ $filterLabels['end_date'] }}
    </p>

    <h2>Attendance Summary</h2>

    <table>
        <thead>
            <tr>
                <th>Sessions</th>
                <th>Total Records</th>
                <th>Present</th>
                <th>Absent</th>
                <th>Late</th>
                <th>Excused</th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>{{ $totalSessions }}</td>
                <td>{{ $attendanceSummary->sum() }}</td>
                <td>{{ $attendanceSummary->get('present', 0) }}</td>
                <td>{{ $attendanceSummary->get('absent', 0) }}</td>
                <td>{{ $attendanceSummary->get('late', 0) }}</td>
                <td>{{ $attendanceSummary->get('excused', 0) }}</td>
            </tr>
        </tbody>
    </table>

    <h2>Attendance Sessions</h2>

    <table>
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
            </tr>
        </thead>

        <tbody>
            @forelse ($sessions as $session)
                @php
                    $assignment = $session->teachingAssignment;
                    $teacher = $assignment?->teacher;
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
                        {{ $teacher
                            ? $teacher->first_name.' '.$teacher->last_name
                            : 'Unavailable' }}
                    </td>

                    <td>{{ $session->student_attendances_count }}</td>
                    <td>{{ $session->present_count }}</td>
                    <td>{{ $session->absent_count }}</td>
                    <td>{{ $session->late_count }}</td>
                    <td>{{ $session->excused_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        No attendance sessions match these filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>