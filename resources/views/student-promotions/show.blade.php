<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotion Details | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />

    <main class="students-page">
        <header class="students-page__header">
            <div>
                <h1>Student Promotion Details</h1>
                <p>Permanent academic progression record.</p>
            </div>

            <a href="{{ route('student-promotions.index') }}"
               class="students-page__button students-page__button--secondary">
                Back to Promotions
            </a>
        </header>

        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="students-page__summary">
            <h2>Promotion Information</h2>

            <table class="students-table">
                <tbody>
                    <tr>
                        <th>Student Number</th>
                        <td>
                            {{ $studentPromotion->student?->student_number ?? 'Unavailable' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Student Name</th>
                        <td>
                            {{ $studentPromotion->student?->first_name }}
                            {{ $studentPromotion->student?->last_name }}
                        </td>
                    </tr>

                    <tr>
                        <th>Previous Class</th>
                        <td>
                            {{ $studentPromotion->sourceEnrollment?->academicClass?->name ?? 'Unavailable' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Previous Academic Year</th>
                        <td>
                            {{ $studentPromotion->sourceEnrollment?->academicClass?->academic_year ?? 'Unavailable' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Destination Class</th>
                        <td>
                            {{ $studentPromotion->destinationEnrollment?->academicClass?->name ?? 'Unavailable' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Destination Academic Year</th>
                        <td>
                            {{ $studentPromotion->destinationEnrollment?->academicClass?->academic_year ?? 'Unavailable' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Progression Type</th>
                        <td>
                            {{ ucfirst($studentPromotion->promotion_type) }}
                        </td>
                    </tr>

                    <tr>
                        <th>Promotion Date</th>
                        <td>
                            {{ $studentPromotion->promotion_date->format('d M Y') }}
                        </td>
                    </tr>

                    <tr>
                        <th>Authorized By</th>
                        <td>
                            {{ $studentPromotion->promotedBy?->name ?? 'Former Administrator' }}
                        </td>
                    </tr>

                    <tr>
                        <th>Notes</th>
                        <td>
                            {{ $studentPromotion->notes ?: 'No notes provided.' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="students-page__summary">
            <h2>Enrollment Records</h2>

            <p>
                Both enrollment records are preserved independently.
            </p>

            <a
                href="{{ route(
                    'student-enrollments.show',
                    $studentPromotion->sourceEnrollment
                ) }}"
                class="students-page__button students-page__button--secondary"
            >
                View Previous Enrollment
            </a>

            <a
                href="{{ route(
                    'student-enrollments.show',
                    $studentPromotion->destinationEnrollment
                ) }}"
                class="students-page__button"
            >
                View New Enrollment
            </a>
        </section>
    </main>
</body>
</html>