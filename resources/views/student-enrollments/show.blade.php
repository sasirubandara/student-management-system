<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Enrollment Details | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>Enrollment Details</h1>
                <p>View the complete student enrollment information.</p>
            </div>

            <div class="student-profile__actions">

                @can('update', $studentEnrollment)
                    <a href="{{ route('student-enrollments.edit', $studentEnrollment) }}"
                       class="students-page__button">
                        Edit Enrollment
                    </a>
                @endcan

                <a href="{{ route('student-enrollments.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Enrollments
                </a>

            </div>

        </header>

        <!-- Success message -->
        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <!-- Enrollment profile -->
        <section class="student-profile">

            <!-- Profile heading -->
            <div class="student-profile__header">

                <div>
                    <h2>
                        {{ $studentEnrollment->student
                            ? $studentEnrollment->student->first_name . ' ' .
                              $studentEnrollment->student->last_name
                            : 'Student Unavailable' }}
                    </h2>

                    <p>
                        Enrollment ID:
                        <strong>{{ $studentEnrollment->id }}</strong>
                    </p>
                </div>

                <span class="students-status
                    {{ $studentEnrollment->status === \App\Models\StudentEnrollment::STATUS_ACTIVE
                        ? 'students-status--active'
                        : 'students-status--other' }}">

                    {{ ucfirst($studentEnrollment->status) }}

                </span>

            </div>

            <!-- Student information -->
            <div class="student-profile__section">

                <h3>Student Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Student Number</span>
                        <strong>
                            {{ $studentEnrollment->student?->student_number ?? 'Unavailable' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Full Name</span>
                        <strong>
                            @if ($studentEnrollment->student)
                                {{ $studentEnrollment->student->first_name }}
                                {{ $studentEnrollment->student->last_name }}
                            @else
                                Unavailable
                            @endif
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Email Address</span>
                        <strong>
                            {{ $studentEnrollment->student?->email ?? 'Not provided' }}
                        </strong>
                    </div>

                </div>

            </div>

            <!-- Academic class information -->
            <div class="student-profile__section">

                <h3>Academic Class Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Class Name</span>
                        <strong>
                            {{ $studentEnrollment->academicClass?->name ?? 'Unavailable' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Class Code</span>
                        <strong>
                            {{ $studentEnrollment->academicClass?->code ?? 'Unavailable' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Grade Level</span>
                        <strong>
                            {{ $studentEnrollment->academicClass?->grade_level ?? 'Unavailable' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Academic Year</span>
                        <strong>
                            {{ $studentEnrollment->academicClass?->academic_year ?? 'Unavailable' }}
                        </strong>
                    </div>

                </div>

            </div>

            <!-- Enrollment information -->
            <div class="student-profile__section">

                <h3>Enrollment Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Enrollment Date</span>
                        <strong>
                            {{ $studentEnrollment->enrollment_date->format('d F Y') }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Enrollment Status</span>
                        <strong>
                            {{ ucfirst($studentEnrollment->status) }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Additional Notes</span>
                        <strong>
                            {{ $studentEnrollment->notes ?? 'No notes provided' }}
                        </strong>
                    </div>

                </div>

            </div>

            <!-- Record information -->
            <div class="student-profile__section">

                <h3>Record Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Created On</span>
                        <strong>
                            {{ $studentEnrollment->created_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Last Updated</span>
                        <strong>
                            {{ $studentEnrollment->updated_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                </div>

            </div>

        </section>

    </main>

</body>
</html>