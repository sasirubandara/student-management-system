<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $student->first_name }} {{ $student->last_name }} | Student Profile</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-authenticated-navigation />
    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">
    <div>
        <h1>Student Profile</h1>
        <p>View the complete registered student information.</p>
    </div>



    <div class="student-profile__actions">

        @can('update', $student)
            <a href="{{ route('students.edit', $student) }}"
                class="students-page__button">
                Edit Student
            </a>
        @endcan

        <a href="{{ route('students.index') }}"
            class="students-page__button students-page__button--secondary">
            Back to Students
        </a>

        @can('delete', $student)

            <form
                action="{{ route('students.destroy', $student) }}"
                method="POST"
                onsubmit="return confirm('Are you sure you want to archive this student?');"
            >
                @csrf
                @method('DELETE')

                <button type="submit"
                    class="students-page__button student-archive-button">
                    Archive Student
                </button>
            </form>

        @endcan

    </div>

        </header>

        @if (session('success'))
    <div class="student-edit-form__success">
        {{ session('success') }}
    </div>
        @endif
        <!-- Profile card -->
        <section class="student-profile">

            <!-- Profile heading -->
            <div class="student-profile__header">

                <div>
                    <h2>
                        {{ $student->first_name }}
                        {{ $student->last_name }}
                    </h2>

                    <p>
                        Student Number:
                        <strong>{{ $student->student_number }}</strong>
                    </p>
                </div>

                <span class="students-status
                    {{ strtolower($student->status) === 'active'
                        ? 'students-status--active'
                        : 'students-status--other' }}">
                    {{ ucfirst($student->status) }}
                </span>

            </div>

            <!-- Personal information -->
            <div class="student-profile__section">

                <h3>Personal Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>First Name</span>
                        <strong>{{ $student->first_name }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Last Name</span>
                        <strong>{{ $student->last_name }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Date of Birth</span>
                        <strong>
                            {{ $student->date_of_birth
                                ? \Illuminate\Support\Carbon::parse($student->date_of_birth)->format('d F Y')
                                : 'Not provided' }}
                        </strong>
                    </div>

                </div>

            </div>

            <!-- Contact information -->
            <div class="student-profile__section">

                <h3>Contact Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Email Address</span>
                        <strong>
                            {{ $student->email ?? 'Not provided' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Phone Number</span>
                        <strong>
                            {{ $student->phone ?? 'Not provided' }}
                        </strong>
                    </div>

                </div>

            </div>

            <!-- Academic information -->
            <div class="student-profile__section">

                <h3>Academic Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Student Number</span>
                        <strong>{{ $student->student_number }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Enrollment Date</span>
                        <strong>
                            {{ \Illuminate\Support\Carbon::parse($student->enrollment_date)->format('d F Y') }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Student Status</span>
                        <strong>{{ ucfirst($student->status) }}</strong>
                    </div>

                </div>

            </div>

            <!-- Record information -->
            <div class="student-profile__section">

                <h3>Record Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Registered On</span>
                        <strong>
                            {{ $student->created_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Last Updated</span>
                        <strong>
                            {{ $student->updated_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                </div>

            </div>

        </section>

    </main>

</body>
</html>