<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Teaching Assignment #{{ $teachingAssignment->id }} | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>Teaching Assignment Details</h1>
                <p>View the teacher, academic class and subject assigned to this record.</p>
            </div>

            <div class="student-profile__actions">

                @can('update', $teachingAssignment)
                    <a href="{{ route('teaching-assignments.edit', $teachingAssignment) }}"
                       class="students-page__button">
                        Edit Assignment
                    </a>
                @endcan

                <a href="{{ route('teaching-assignments.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Assignments
                </a>

                @can('delete', $teachingAssignment)

                    <form
                        action="{{ route('teaching-assignments.destroy', $teachingAssignment) }}"
                        method="POST"
                        onsubmit="return confirm('Are you sure you want to remove this teaching assignment?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="students-page__button student-archive-button">
                            Remove Assignment
                        </button>
                    </form>

                @endcan

            </div>

        </header>

        <!-- Success message -->
        @if (session('success'))
            <div class="student-edit-form__success" role="status">
                {{ session('success') }}
            </div>
        @endif

        <!-- Assignment details -->
        <section class="student-profile">

            <!-- Assignment heading -->
            <div class="student-profile__header">

                <div>
                    <h2>Teaching Assignment #{{ $teachingAssignment->id }}</h2>

                    <p>
                        Assigned on:
                        <strong>
                            {{ $teachingAssignment->created_at->format('d F Y') }}
                        </strong>
                    </p>
                </div>

            </div>

            <!-- Teacher information -->
            <div class="student-profile__section">

                <h3>Teacher Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Teacher Number</span>
                        <strong>
                            {{ $teachingAssignment->teacher?->teacher_number ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Full Name</span>
                        <strong>
                            {{ $teachingAssignment->teacher
                                ? $teachingAssignment->teacher->first_name . ' ' . $teachingAssignment->teacher->last_name
                                : 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Email Address</span>
                        <strong>
                            {{ $teachingAssignment->teacher?->email ?? 'Not provided' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Phone Number</span>
                        <strong>
                            {{ $teachingAssignment->teacher?->phone ?? 'Not provided' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Teacher Status</span>

                        <strong>
                            {{ $teachingAssignment->teacher
                                ? ucfirst($teachingAssignment->teacher->status)
                                : 'Not available' }}
                        </strong>
                    </div>

                </div>

                @if ($teachingAssignment->teacher)
                    <a href="{{ route('teachers.show', $teachingAssignment->teacher) }}"
                       class="students-table__view">
                        View Teacher Profile
                    </a>
                @endif

            </div>

            <!-- Academic class information -->
            <div class="student-profile__section">

                <h3>Academic Class Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Class Name</span>
                        <strong>
                            {{ $teachingAssignment->academicClass?->name ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Class Code</span>
                        <strong>
                            {{ $teachingAssignment->academicClass?->code ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Grade Level</span>
                        <strong>
                            {{ $teachingAssignment->academicClass?->grade_level ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Academic Year</span>
                        <strong>
                            {{ $teachingAssignment->academicClass?->academic_year ?? 'Not available' }}
                        </strong>
                    </div>

                </div>

                @if ($teachingAssignment->academicClass)
                    <a href="{{ route('academic-classes.show', $teachingAssignment->academicClass) }}"
                       class="students-table__view">
                        View Academic Class
                    </a>
                @endif

            </div>

            <!-- Subject information -->
            <div class="student-profile__section">

                <h3>Subject Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Subject Name</span>
                        <strong>
                            {{ $teachingAssignment->subject?->name ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Subject Code</span>
                        <strong>
                            {{ $teachingAssignment->subject?->code ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Description</span>
                        <strong>
                            {{ $teachingAssignment->subject?->description ?? 'Not provided' }}
                        </strong>
                    </div>

                </div>

                @if ($teachingAssignment->subject)
                    <a href="{{ route('subjects.show', $teachingAssignment->subject) }}"
                       class="students-table__view">
                        View Subject
                    </a>
                @endif

            </div>

            <!-- Assignment record information -->
            <div class="student-profile__section">

                <h3>Record Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Assignment ID</span>
                        <strong>{{ $teachingAssignment->id }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Assigned On</span>
                        <strong>
                            {{ $teachingAssignment->created_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Last Updated</span>
                        <strong>
                            {{ $teachingAssignment->updated_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                </div>

            </div>

        </section>

    </main>

</body>
</html>