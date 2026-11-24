<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $academicYear->name }} | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />
    <main class="students-page">
        <header class="students-page__header">
            <div>
                <h1>{{ $academicYear->name }}</h1>
                <p>Academic year information and scheduled terms.</p>
            </div>
            <div class="students-page__header-actions">
                <a href="{{ route('academic-years.index') }}" class="students-page__button students-page__button--secondary">Back to Academic Years</a>
                @can('update', $academicYear)
                    <a href="{{ route('academic-years.edit', $academicYear) }}" class="students-page__button">Edit Academic Year</a>
                @endcan
            </div>
        </header>
        @if (session('success'))
            <div class="student-edit-form__success" role="status">{{ session('success') }}</div>
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
        <section class="students-page__summary">
            <h2>Academic Year Information</h2>
            <div class="students-page__table-wrapper">
                <table class="students-table">
                    <tbody>
                        <tr><th scope="row">Name</th><td>{{ $academicYear->name }}</td></tr>
                        <tr><th scope="row">Year Identifier</th><td>{{ $academicYear->year }}</td></tr>
                        <tr><th scope="row">Start Date</th><td>{{ $academicYear->start_date->format('d M Y') }}</td></tr>
                        <tr><th scope="row">End Date</th><td>{{ $academicYear->end_date->format('d M Y') }}</td></tr>
                        <tr><th scope="row">Number of Terms</th><td>{{ $academicYear->terms->count() }}</td></tr>
                        <tr><th scope="row">Created</th><td>{{ $academicYear->created_at?->format('d M Y, h:i A') ?? 'Not available' }}</td></tr>
                        <tr><th scope="row">Last Updated</th><td>{{ $academicYear->updated_at?->format('d M Y, h:i A') ?? 'Not available' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="students-page__summary">
            <div class="students-page__header">
                <div>
                    <h2>Academic Terms</h2>
                    <p>Terms scheduled within this academic year.</p>
                </div>
                @can('create', \App\Models\AcademicTerm::class)
                    <a href="{{ route('academic-years.terms.create', $academicYear) }}" class="students-page__button">Create Term</a>
                @endcan
            </div>
            @if ($academicYear->terms->isEmpty())
                <p>No academic terms have been created for this year yet.</p>
            @else
                <div class="students-page__table-wrapper">
                    <table class="students-table">
                        <thead><tr><th>Order</th><th>Name</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
                        <tbody>
                            @foreach ($academicYear->terms as $academicTerm)
                                <tr>
                                    <td>{{ $academicTerm->term_number }}</td>
                                    <td><strong>{{ $academicTerm->name }}</strong></td>
                                    <td>{{ $academicTerm->start_date->format('d M Y') }}</td>
                                    <td>{{ $academicTerm->end_date->format('d M Y') }}</td>
                                    <td>
                                        @can('update', $academicTerm)
                                            <a href="{{ route('academic-terms.edit', $academicTerm) }}" class="students-table__view">Edit</a>
                                        @else
                                            <span>Read-only</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

    </main>
</body>
</html>
