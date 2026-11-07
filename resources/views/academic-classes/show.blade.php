<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $academicClass->name }} | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>{{ $academicClass->name }}</h1>
                <p>Academic class details.</p>
            </div>

            <div class="students-page__header-actions">
                <a href="{{ route('academic-classes.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Classes
                </a>

                @can('update', $academicClass)
                    <a href="{{ route('academic-classes.edit', $academicClass) }}"
                       class="students-page__button">
                        Edit Class
                    </a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="student-edit-form__success">
                {{ session('success') }}
            </div>
        @endif

        <section class="students-page__summary">

            <table class="students-table">

                <tbody>
                    <tr>
                        <th>Class Name</th>
                        <td>{{ $academicClass->name }}</td>
                    </tr>

                    <tr>
                        <th>Class Code</th>
                        <td>{{ $academicClass->code }}</td>
                    </tr>

                    <tr>
                        <th>Grade Level</th>
                        <td>Grade {{ $academicClass->grade_level }}</td>
                    </tr>

                    <tr>
                        <th>Academic Year</th>
                        <td>{{ $academicClass->academic_year }}</td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="students-status
                                {{ $academicClass->status === 'active'
                                    ? 'students-status--active'
                                    : 'students-status--other' }}">
                                {{ ucfirst($academicClass->status) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th>Description</th>
                        <td>
                            {{ $academicClass->description ?: 'No description provided.' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Created</th>
                        <td>
                            {{ $academicClass->created_at?->format('d M Y, h:i A') ?? 'Not available' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Last Updated</th>
                        <td>
                            {{ $academicClass->updated_at?->format('d M Y, h:i A') ?? 'Not available' }}
                        </td>
                    </tr>
                </tbody>

            </table>

        </section>

        @can('delete', $academicClass)
            <section class="students-page__summary">

                <h2>Delete Academic Class</h2>

                <p>
                    Deleting this class will archive it.
                    Its database record will be retained.
                </p>

                <form
                    action="{{ route('academic-classes.destroy', $academicClass) }}"
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this academic class?');"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="students-page__button">
                        Delete Class
                    </button>
                </form>

            </section>
        @endcan

    </main>

</body>
</html>