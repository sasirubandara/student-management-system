<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Enrollment | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <h1>Edit Student Enrollment</h1>
            <p>Update the enrollment date, status, and additional notes.</p>
        </header>

        <a href="{{ route('student-enrollments.show', $studentEnrollment) }}"
           class="students-page__button students-page__button--secondary">
            Back to Enrollment Details
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

        <form action="{{ route('student-enrollments.update', $studentEnrollment) }}"
              method="POST">

            @csrf
            @method('PUT')

            {{-- Student information (read-only) --}}
            <div class="form-group">

                <label>Student</label>

                <input
                    type="text"
                    value="{{ $studentEnrollment->student
                        ? $studentEnrollment->student->student_number . ' - ' .
                          $studentEnrollment->student->first_name . ' ' .
                          $studentEnrollment->student->last_name
                        : 'Student Unavailable' }}"
                    disabled
                >

            </div>

            {{-- Academic class information (read-only) --}}
            <div class="form-group">

                <label>Academic Class</label>

                <input
                    type="text"
                    value="{{ $studentEnrollment->academicClass
                        ? $studentEnrollment->academicClass->name . ' (' .
                          $studentEnrollment->academicClass->code . ')'
                        : 'Academic Class Unavailable' }}"
                    disabled
                >

            </div>

            <div class="form-grid">

                {{-- Enrollment date --}}
                <div class="form-group">

                    <label for="enrollment_date">
                        Enrollment Date *
                    </label>

                    <input
                        type="date"
                        id="enrollment_date"
                        name="enrollment_date"
                        value="{{ old(
                            'enrollment_date',
                            $studentEnrollment->enrollment_date->format('Y-m-d')
                        ) }}"
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

                    <select id="status" name="status" required>

                        <option
                            value="active"
                            @selected(old('status', $studentEnrollment->status) === 'active')
                        >
                            Active
                        </option>

                        <option
                            value="completed"
                            @selected(old('status', $studentEnrollment->status) === 'completed')
                        >
                            Completed
                        </option>

                        <option
                            value="withdrawn"
                            @selected(old('status', $studentEnrollment->status) === 'withdrawn')
                        >
                            Withdrawn
                        </option>

                    </select>

                    @error('status')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

            </div>

            {{-- Additional notes --}}
            <div class="form-group">

                <label for="notes">
                    Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="5"
                    maxlength="5000"
                    placeholder="Enter additional enrollment information..."
                >{{ old('notes', $studentEnrollment->notes) }}</textarea>

                @error('notes')
                    <span class="field-error">{{ $message }}</span>
                @enderror

            </div>

            <div class="form-actions">

                <button type="submit">
                    Update Enrollment
                </button>

                <a href="{{ route('student-enrollments.show', $studentEnrollment) }}"
                   class="students-page__button students-page__button--secondary">
                    Cancel
                </a>

            </div>

        </form>

    </main>

</body>
</html>