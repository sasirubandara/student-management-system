<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Years | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />
    <main class="students-page">
        <header class="students-page__header">
            <div>
                <h1>Academic Year Management</h1>
                <p>View academic years and their term schedules.</p>
            </div>
            @can('create', \App\Models\AcademicYear::class)
                <a href="{{ route('academic-years.create') }}" class="students-page__button">Create Academic Year</a>
            @endcan
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
        <section class="students-search-section">
            <form action="{{ route('academic-years.index') }}" method="GET" class="students-search">
                <div class="students-search__input-wrapper">
                    <label for="academic-year-search" class="sr-only">Search academic years</label>
                    <input type="search" id="academic-year-search" name="search" value="{{ $search }}"
                           placeholder="Search by academic year name or year..." maxlength="255">
                </div>
                <button type="submit" class="students-page__button">Search</button>
                @if ($search !== '')
                    <a href="{{ route('academic-years.index') }}" class="students-search__clear">Clear Search</a>
                @endif
            </form>
        </section>
        <section class="students-page__summary">
            <p>{{ $search !== '' ? 'Matching academic years:' : 'Total academic years:' }}
                <strong>{{ $academicYears->total() }}</strong></p>
            @if ($search !== '')
                <p class="students-search__description">Search results for: <strong>{{ $search }}</strong></p>
            @endif
        </section>
        @if ($academicYears->isEmpty())
            <section class="students-page__empty">
                <h2>{{ $search !== '' ? 'No Matching Academic Years' : 'No Academic Years Found' }}</h2>
                <p>{{ $search !== '' ? 'Try another name or year identifier.' : 'No academic years have been registered yet.' }}</p>
                @if ($search !== '')
                    <a href="{{ route('academic-years.index') }}" class="students-page__button">View All Academic Years</a>
                @endif
            </section>
        @else
            <div class="students-page__table-wrapper">
                <table class="students-table">
                    <thead><tr><th>Name</th><th>Year Identifier</th><th>Start</th><th>End</th><th>Terms</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach ($academicYears as $academicYear)
                            <tr>
                                <td><strong>{{ $academicYear->name }}</strong></td>
                                <td>{{ $academicYear->year }}</td>
                                <td>{{ $academicYear->start_date->format('d M Y') }}</td>
                                <td>{{ $academicYear->end_date->format('d M Y') }}</td>
                                <td>{{ $academicYear->terms_count }}</td>
                                <td>
                                    <a href="{{ route('academic-years.show', $academicYear) }}" class="students-table__view">View</a>
                                    @can('update', $academicYear)
                                        <a href="{{ route('academic-years.edit', $academicYear) }}" class="students-table__view">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="students-page__pagination">{{ $academicYears->links() }}</div>
        @endif

    </main>
</body>
</html>
