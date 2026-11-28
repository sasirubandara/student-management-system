<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promote Student | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />

    <main class="students-page">
        <header class="students-page__header">
            <div>
                <h1>Promote Student</h1>
                <p>
                    Progress a student to a later academic year
                    while preserving enrollment history.
                </p>
            </div>

            <a href="{{ route('student-promotions.index') }}"
               class="students-page__button students-page__button--secondary">
                Back to Promotions
            </a>
        </header>

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

        <section class="students-page__form-wrapper">
            @if ($sourceEnrollments->isEmpty() || $academicClasses->isEmpty())
                <div class="error-summary" role="alert">
                    <strong>Promotion cannot be started yet.</strong>

                    @if ($sourceEnrollments->isEmpty())
                        <p>No eligible active enrollments are available.</p>
                    @endif

                    @if ($academicClasses->isEmpty())
                        <p>
                            No active classes with registered
                            academic years are available.
                        </p>
                    @endif
                </div>
            @else
                <form
                    action="{{ route('student-promotions.store') }}"
                    method="POST"
                    class="student-edit-form"
                >
                    @csrf

                    <div class="student-edit-form__field">
                        <label for="source_enrollment_id">
                            Current Enrollment
                        </label>

                        <select
                            id="source_enrollment_id"
                            name="source_enrollment_id"
                            required
                        >
                            <option value="">Select Enrollment</option>

                            @foreach ($sourceEnrollments as $enrollment)
                                <option
                                    value="{{ $enrollment->id }}"
                                    @selected(
                                        (string) old(
                                            'source_enrollment_id',
                                            $selectedSourceId
                                        ) === (string) $enrollment->id
                                    )
                                >
                                    {{ $enrollment->student?->student_number }}
                                    -
                                    {{ $enrollment->student?->first_name }}
                                    {{ $enrollment->student?->last_name }}
                                    |
                                    {{ $enrollment->academicClass?->name }}
                                    ({{ $enrollment->academicClass?->academic_year }})
                                </option>
                            @endforeach
                        </select>

                        @error('source_enrollment_id')
                            <p class="student-edit-form__error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="student-edit-form__field">
                        <label for="destination_class_id">
                            Destination Academic Class
                        </label>

                        <select
                            id="destination_class_id"
                            name="destination_class_id"
                            required
                        >
                            <option value="">Select Destination</option>

                            @foreach ($academicClasses as $academicClass)
                                <option
                                    value="{{ $academicClass->id }}"
                                    @selected(
                                        old('destination_class_id') ==
                                        $academicClass->id
                                    )
                                >
                                    {{ $academicClass->name }}
                                    -
                                    Grade {{ $academicClass->grade_level }}
                                    ({{ $academicClass->academic_year }})
                                </option>
                            @endforeach
                        </select>

                        @error('destination_class_id')
                            <p class="student-edit-form__error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="student-edit-form__field">
                        <label for="promotion_type">Progression Type</label>

                        <select
                            id="promotion_type"
                            name="promotion_type"
                            required
                        >
                            <option value="">Select Type</option>

                            <option
                                value="advanced"
                                @selected(old('promotion_type') === 'advanced')
                            >
                                Advanced to Next Grade
                            </option>

                            <option
                                value="repeated"
                                @selected(old('promotion_type') === 'repeated')
                            >
                                Repeat Current Grade
                            </option>
                        </select>

                        @error('promotion_type')
                            <p class="student-edit-form__error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="student-edit-form__field">
                        <label for="promotion_date">Promotion Date</label>

                        <input
                            type="date"
                            id="promotion_date"
                            name="promotion_date"
                            value="{{ old('promotion_date') }}"
                            required
                        >

                        @error('promotion_date')
                            <p class="student-edit-form__error">
                                {{ $message }}
                            </p>
                        @enderror

                        <p>
                            Choose a date within the destination
                            academic year's registered date range.
                        </p>
                    </div>

                    <div class="student-edit-form__field">
                        <label for="notes">Administrative Notes</label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            maxlength="5000"
                            placeholder="Optional progression explanation..."
                        >{{ old('notes') }}</textarea>

                        @error('notes')
                            <p class="student-edit-form__error">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <p>
                        This action will complete the original
                        enrollment and create a new active enrollment.
                        Existing attendance and examination history
                        will be preserved.
                    </p>

                    <div class="student-edit-form__actions">
                        <button
                            type="submit"
                            class="students-page__button"
                        >
                            Confirm Promotion
                        </button>

                        <a
                            href="{{ route('student-promotions.index') }}"
                            class="students-search__clear"
                        >
                            Cancel
                        </a>
                    </div>
                </form>
            @endif
        </section>
    </main>
</body>
</html>