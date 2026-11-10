<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Attendance Session | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <h1>Create Attendance Session</h1>
            <p>Select a teaching assignment and attendance date.</p>
        </header>

        <a href="{{ route('attendance-sessions.index') }}"
           class="students-page__button students-page__button--secondary">
            Back to Attendance
        </a>

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

        @if ($teachingAssignments->isEmpty())

            <section class="students-page__empty">
                <h2>No Available Teaching Assignments</h2>
                <p>
                    You need an active teacher, academic class and subject assignment
                    before creating an attendance session.
                </p>
            </section>

        @else

            <form action="{{ route('attendance-sessions.store') }}" method="POST">

                @csrf

                <div class="form-grid">

                    <div class="form-group">

                        <label for="teaching_assignment_id">
                            Teaching Assignment *
                        </label>

                        <select
                            id="teaching_assignment_id"
                            name="teaching_assignment_id"
                            required
                        >

                            <option value="">Select a teaching assignment</option>

                            @foreach ($teachingAssignments as $assignment)
                                <option
                                    value="{{ $assignment->id }}"
                                    @selected(old('teaching_assignment_id') == $assignment->id)
                                >
                                    {{ $assignment->teacher->teacher_number }}
                                    -
                                    {{ $assignment->teacher->first_name }}
                                    {{ $assignment->teacher->last_name }}
                                    |
                                    {{ $assignment->academicClass->name }}
                                    |
                                    {{ $assignment->subject->name }}
                                </option>
                            @endforeach

                        </select>

                        @error('teaching_assignment_id')
                            <span class="field-error">{{ $message }}</span>
                        @enderror

                    </div>

                    <div class="form-group">

                        <label for="attendance_date">
                            Attendance Date *
                        </label>

                        <input
                            type="date"
                            id="attendance_date"
                            name="attendance_date"
                            value="{{ old('attendance_date', now()->toDateString()) }}"
                            max="{{ now()->toDateString() }}"
                            required
                        >

                        @error('attendance_date')
                            <span class="field-error">{{ $message }}</span>
                        @enderror

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit">
                        Create Attendance Session
                    </button>

                    <a href="{{ route('attendance-sessions.index') }}"
                       class="students-page__button students-page__button--secondary">
                        Cancel
                    </a>

                </div>

            </form>

        @endif

    </main>

</body>
</html>