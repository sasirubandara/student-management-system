<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Import Students | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <h1>Import Students from CSV</h1>
            <p>
                Upload a CSV file to register multiple student records.
            </p>
        </header>

        @if (session('success'))
            <div class="success-message" role="status">
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

        @if (session('import_result'))
            @php
                $result = session('import_result');
            @endphp

            <section class="report-panel">

                <h2>Import Results</h2>

                <div class="report-stats-grid">

                    <article class="report-stat-card">
                        <span>Successfully Imported</span>
                        <strong>{{ $result['imported'] }}</strong>
                    </article>

                    <article class="report-stat-card">
                        <span>Skipped Rows</span>
                        <strong>{{ $result['failed'] }}</strong>
                    </article>

                </div>

                @if ($result['file_error'])
                    <div class="error-summary" role="alert">
                        {{ $result['file_error'] }}
                    </div>
                @endif

                @if (count($result['errors']) > 0)
                    <div class="import-errors">

                        <h3>Rows Requiring Attention</h3>

                        <ul>
                            @foreach ($result['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>

                    </div>
                @endif

            </section>
        @endif

        <section class="report-panel">

            <h2>CSV File Requirements</h2>

            <p>
                Download the template before preparing your CSV file.
                Keep the column names unchanged.
            </p>

            <p>
                Required columns:
                <strong>student_number, first_name, last_name, enrollment_date</strong>
            </p>

            <p>
                Optional columns:
                <strong>email, phone, date_of_birth, status</strong>
            </p>

            <p>
                If the status column is omitted or left empty, the student
                will be registered with Active status.
            </p>

            <p>
                Accepted file types: CSV. Maximum file size: 2 MB.
            </p>

            <a
                href="{{ route('students.import.template') }}"
                class="students-page__button students-page__button--secondary"
            >
                Download CSV Template
            </a>

        </section>

        <section class="report-panel">

            <h2>Upload Student CSV</h2>

            <form
                action="{{ route('students.import.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="form-group">

                    <label for="file">Select CSV File *</label>

                    <input
                        type="file"
                        id="file"
                        name="file"
                        accept=".csv,text/csv,text/plain"
                        required
                    >

                    @error('file')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                <div class="form-actions">
                    <button type="submit">
                        Import Students
                    </button>

                    <a href="{{ route('students.index') }}">
                        Cancel
                    </a>
                </div>

            </form>

        </section>

    </main>

</body>
</html>