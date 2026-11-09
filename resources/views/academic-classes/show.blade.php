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

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>{{ $academicClass->name }}</h1>
                <p>Academic class details and assigned subjects.</p>
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

        <!-- Success message -->
        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <!-- Validation errors -->
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

        <!-- Academic class information -->
        <section class="students-page__summary">

            <h2>Class Information</h2>

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

        <!-- Assigned subjects -->
        <section class="students-page__summary">

            <h2>Assigned Subjects</h2>

            <p>
                Subjects currently assigned to this academic class.
            </p>

            @if ($academicClass->subjects->isEmpty())

                <p>No subjects have been assigned to this class yet.</p>

            @else

                <div class="students-page__table-wrapper">

                    <table class="students-table">

                        <thead>
                            <tr>
                                <th>Subject Name</th>
                                <th>Subject Code</th>
                                <th>Status</th>

                                @can('update', $academicClass)
                                    <th>Actions</th>
                                @endcan
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($academicClass->subjects as $subject)

                                <tr>

                                    <td>
                                        <strong>{{ $subject->name }}</strong>
                                    </td>

                                    <td>{{ $subject->code }}</td>

                                    <td>
                                        <span class="students-status
                                            {{ $subject->status === 'active'
                                                ? 'students-status--active'
                                                : 'students-status--other' }}">
                                            {{ ucfirst($subject->status) }}
                                        </span>
                                    </td>

                                    @can('update', $academicClass)
                                        <td>

                                            <form
                                                action="{{ route(
                                                    'academic-classes.subjects.destroy',
                                                    [$academicClass, $subject]
                                                ) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to remove this subject from the class?');"
                                            >

                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="students-page__button student-archive-button"
                                                >
                                                    Remove
                                                </button>

                                            </form>

                                        </td>
                                    @endcan

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </section>

        <!-- Assign new subjects -->
        @can('update', $academicClass)

            <section class="students-page__summary">

                <h2>Assign Subjects</h2>

                <p>
                    Select one or more active subjects to assign to this class.
                </p>

                @if ($availableSubjects->isEmpty())

                    <p>
                        There are no additional active subjects available for assignment.
                    </p>

                @else

                    <form
                        action="{{ route('academic-classes.subjects.store', $academicClass) }}"
                        method="POST"
                    >

                        @csrf

                        <div class="form-group">

                            <fieldset>

                                <legend>Select Subjects</legend>

                                @foreach ($availableSubjects as $subject)

                                    <label class="form-group">

                                        <input
                                            type="checkbox"
                                            name="subject_ids[]"
                                            value="{{ $subject->id }}"
                                            @checked(
                                                in_array(
                                                    $subject->id,
                                                    old('subject_ids', [])
                                                )
                                            )
                                        >

                                        {{ $subject->name }}
                                        ({{ $subject->code }})

                                    </label>

                                @endforeach

                            </fieldset>

                            @error('subject_ids')
                                <span class="field-error">{{ $message }}</span>
                            @enderror

                            @error('subject_ids.*')
                                <span class="field-error">{{ $message }}</span>
                            @enderror

                        </div>

                        <div class="form-actions">

                            <button type="submit">
                                Assign Selected Subjects
                            </button>

                        </div>

                    </form>

                @endif

            </section>

        @endcan

        <!-- Delete academic class -->
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