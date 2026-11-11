<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Reports</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="reports-page">

        <header class="report-header">
            <div>
                <h1>Academic Reports</h1>
                <p>
                    View student academic history, attendance records,
                    examination results and grade summaries.
                </p>
            </div>
        </header>

        <div class="report-options">

            @can('viewAny', \App\Models\Student::class)
                <a
                    href="{{ route('reports.students.index') }}"
                    class="report-option"
                >
                    <h2>Student Academic Reports</h2>
                    <p>
                        View individual student enrollment history,
                        attendance records and examination results.
                    </p>
                    <span>View Student Reports →</span>
                </a>
            @endcan

            @can('viewAny', \App\Models\AttendanceSession::class)
                <a
                    href="{{ route('reports.attendance') }}"
                    class="report-option"
                >
                    <h2>Attendance Reports</h2>
                    <p>
                        Review attendance sessions and attendance
                        totals by class, subject and date range.
                    </p>
                    <span>View Attendance Reports →</span>
                </a>
            @endcan

            @can('viewAny', \App\Models\Examination::class)
                <a
                    href="{{ route('reports.examinations') }}"
                    class="report-option"
                >
                    <h2>Examination Reports</h2>
                    <p>
                        Review examinations, recorded results
                        and grade distributions.
                    </p>
                    <span>View Examination Reports →</span>
                </a>
            @endcan

        </div>

    </main>

</body>
</html>