<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $subject->name }} | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <header class="students-page__header">
            <div>
                <h1>{{ $subject->name }}</h1>
                <p>Subject details and information.</p>
            </div>

            <div class="students-page__header-actions">

                <a href="{{ route('subjects.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Subjects
                </a>

                @can('update', $subject)
                    <a href="{{ route('subjects.edit', $subject) }}"
                       class="students-page__button">
                        Edit Subject
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
                        <th>Subject Name</th>
                        <td>{{ $subject->name }}</td>
                    </tr>

                    <tr>
                        <th>Subject Code</th>
                        <td>{{ $subject->code }}</td>
                    </tr>

                    <tr>
                        <th>Description</th>
                        <td>
                            {{ $subject->description ?: 'No description provided.' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="students-status
                                {{ $subject->status === 'active'
                                    ? 'students-status--active'
                                    : 'students-status--other' }}">
                                {{ ucfirst($subject->status) }}
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <th>Created</th>
                        <td>
                            {{ $subject->created_at?->format('d M Y, h:i A') ?? 'Not available' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Last Updated</th>
                        <td>
                            {{ $subject->updated_at?->format('d M Y, h:i A') ?? 'Not available' }}
                        </td>
                    </tr>

                </tbody>

            </table>

        </section>

        @can('delete', $subject)
            <section class="students-page__summary">

                <h2>Delete Subject</h2>

                <p>
                    Deleting this subject will archive it.
                    Its database record will be retained.
                </p>

                <form
                    action="{{ route('subjects.destroy', $subject) }}"
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this subject?');"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="students-page__button">
                        Delete Subject
                    </button>
                </form>

            </section>
        @endcan

    </main>

</body>
</html>