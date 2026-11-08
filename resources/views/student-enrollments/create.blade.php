<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Enrollment | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <h1>Create Student Enrollment</h1>
            <p>Assign a student to an academic class.</p>
        </header>

        <a href="{{ route('student-enrollments.index') }}"
           class="students-page__button students-page__button--secondary">
            Back to Enrollments
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

        @if ($students->isEmpty() || $academicClasses->isEmpty())

            <div class="error-summary" role="alert">

                <strong>Enrollment cannot be created yet.</strong>

                @if ($students->isEmpty())
                    <p>There are no active students available.</p>
                    <a href="{{ route('students.create') }}">
                        Register a Student
                    </a>
                @endif

                @if ($academicClasses->isEmpty())
                    <p>There are no active academic classes available.</p>
                    <a href="{{ route('academic-classes.create') }}">
                        Create an Academic Class
                    </a>
                @endif

            </div>

        @else

            <form action="{{ route('student-enrollments.store') }}"
                  method="POST">

                @csrf

                <div class="form-grid">

                    {{-- Student selection --}}
                    <div class="form-group">

                        <label for="student_id">
                            Select Student *
                        </label>

                        <select id="student_id"
                                name="student_id"
                                required>

                            <option value="">-- Select a Student --</option>

                            @foreach ($students as $student)

                                <option
                                    value="{{ $student->id }}"
                                    @selected(old('student_id') == $student->id)
                                >
                                    {{ $student->student_number }}
                                    -
                                    {{ $student->first_name }}
                                    {{ $student->last_name }}
                                </option>

                            @endforeach

                        </select>

                        @error('student_id')
                            <span class="field-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Academic class selection --}}
                    <div class="form-group">

                        <label for="academic_class_id">
                            Select Academic Class *
                        </label>

                        <select id="academic_class_id"
                                name="academic_class_id"
                                required>

                            <option value="">-- Select an Academic Class --</option>

                            @foreach ($academicClasses as $academicClass)

                                <option
                                    value="{{ $academicClass->id }}"
                                    @selected(old('academic_class_id') == $academicClass->id)
                                >
                                    {{ $academicClass->name }}
                                    ({{ $academicClass->code }})
                                    - Grade {{ $academicClass->grade_level }}
                                    - {{ $academicClass->academic_year }}
                                </option>

                            @endforeach

                        </select>

                        @error('academic_class_id')
                            <span class="field-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Enrollment date --}}
                    <div class="form-group">

                        <label for="enrollment_date">
                            Enrollment Date *
                        </label>

                        <input
                            type="date"
                            id="enrollment_date"
                            name="enrollment_date"
                            value="{{ old('enrollment_date', now()->toDateString()) }}"
                            required
                        >

                        @error('enrollment_date')
                            <span class="field-error">{{ $message }}</span>
                        @enderror

                    </div>

                    {{-- Enrollment status --}}
                    <div class="form-group">

                        <label for="status">
                            Enrollment Status *
                        </label>

                        <select id="status"
                                name="status"
                                required>

                            <option
                                value="active"
                                @selected(old('status', 'active') === 'active')
                            >
                                Active
                            </option>

                            <option
                                value="completed"
                                @selected(old('status') === 'completed')
                            >
                                Completed
                            </option>

                            <option
                                value="withdrawn"
                                @selected(old('status') === 'withdrawn')
                            >
                                Withdrawn
                            </option>

                        </select>

                        @error('status')
                            <span class="field-error">{{ $message }}</span>
                        @enderror

                    </div>

                </div>

                {{-- Optional notes --}}
                <div class="form-group">

                    <label for="notes">
                        Notes
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        maxlength="5000"
                        placeholder="Enter any additional enrollment information..."
                    >{{ old('notes') }}</textarea>

                    @error('notes')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                <div class="form-actions">

                    <button type="submit">
                        Create Enrollment
                    </button>

                    <a href="{{ route('student-enrollments.index') }}"
                       class="students-page__button students-page__button--secondary">
                        Cancel
                    </a>

                </div>

            </form>

        @endif

    </main>

</body>
</html>