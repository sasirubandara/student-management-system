<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Academic Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
        }

        h1, h2 {
            color: #1e3a8a;
        }

        h1 {
            font-size: 20px;
            text-align: center;
            margin-bottom: 5px;
        }

        h2 {
            font-size: 13px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
            margin-top: 20px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            padding: 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
            vertical-align: top;
            overflow-wrap: break-word;
        }

        th {
            background: #eff6ff;
        }

        tr {
            page-break-inside: avoid;
        }

        .summary {
            margin: 12px 0;
        }
    </style>
</head>
<body>
    <h1>{{ $institutionName }}</h1>

    <div class="subtitle">
        Student Academic Report
    </div>

    <p>
        <strong>Student Number:</strong>
        {{ $student->student_number }}
    </p>

    <p>
        <strong>Name:</strong>
        {{ $student->first_name }} {{ $student->last_name }}
    </p>

    <p>
        <strong>Generated:</strong>
        {{ $generatedAt }}
    </p>

    <h2>Enrollment Summary</h2>

    <p class="summary">
        Total: {{ $enrollmentStats['total'] }} |
        Active: {{ $enrollmentStats['active'] }} |
        Completed: {{ $enrollmentStats['completed'] }} |
        Withdrawn: {{ $enrollmentStats['withdrawn'] }}
    </p>

    <h2>Enrollment History</h2>

    <table>
        <thead>
            <tr>
                <th>Class</th>
                <th>Enrollment Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($enrollments as $enrollment)
                <tr>
                    <td>
                        {{ $enrollment->academicClass?->name ?? 'N/A' }}
                    </td>
                    <td>{{ $enrollment->enrollment_date }}</td>
                    <td>{{ ucfirst($enrollment->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No enrollment records.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Attendance Summary</h2>

    <p>
        Present: {{ $attendanceSummary->get('present', 0) }} |
        Absent: {{ $attendanceSummary->get('absent', 0) }} |
        Late: {{ $attendanceSummary->get('late', 0) }} |
        Excused: {{ $attendanceSummary->get('excused', 0) }}
    </p>

    <h2>Attendance History</h2>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendanceRecords as $record)
                <tr>
                    <td>
                        {{ $record->attendanceSession?->attendance_date ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $record->attendanceSession?->teachingAssignment?->subject?->name ?? 'N/A' }}
                    </td>
                    <td>{{ ucfirst($record->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No attendance records.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Examination Grade Summary</h2>

    <p>
        @forelse ($gradeSummary as $grade => $total)
            {{ $grade }}: {{ $total }}
            @unless ($loop->last) | @endunless
        @empty
            No recorded grades.
        @endforelse
    </p>

    <h2>Examination Results</h2>

    <table>
        <thead>
            <tr>
                <th>Examination</th>
                <th>Marks</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($examinationResults as $result)
                <tr>
                    <td>
                        {{ $result->examination?->title ?? 'N/A' }}
                    </td>
                    <td>
                        {{ $result->marks_obtained }}
                        /
                        {{ $result->examination?->maximum_marks ?? 'N/A' }}
                    </td>
                    <td>{{ $result->grade ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No examination results.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>