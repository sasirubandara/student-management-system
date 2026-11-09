<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $teacher->first_name }} {{ $teacher->last_name }} | Teacher Profile</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="students-page">

        <!-- Page header -->
        <header class="students-page__header">

            <div>
                <h1>Teacher Profile</h1>
                <p>View the complete registered teacher information.</p>
            </div>

            <div class="student-profile__actions">

                @can('update', $teacher)
                    <a href="{{ route('teachers.edit', $teacher) }}"
                       class="students-page__button">
                        Edit Teacher
                    </a>
                @endcan

                <a href="{{ route('teachers.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Back to Teachers
                </a>

                @can('delete', $teacher)

                    <form
                        action="{{ route('teachers.destroy', $teacher) }}"
                        method="POST"
                        onsubmit="return confirm('Are you sure you want to archive this teacher?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="students-page__button student-archive-button">
                            Archive Teacher
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

        <!-- Teacher profile -->
        <section class="student-profile">

            <!-- Profile heading -->
            <div class="student-profile__header">

                <div>
                    <h2>
                        {{ $teacher->first_name }}
                        {{ $teacher->last_name }}
                    </h2>

                    <p>
                        Teacher Number:
                        <strong>{{ $teacher->teacher_number }}</strong>
                    </p>
                </div>

                <span class="students-status
                    {{ $teacher->status === \App\Models\Teacher::STATUS_ACTIVE
                        ? 'students-status--active'
                        : 'students-status--other' }}">

                    {{ ucfirst($teacher->status) }}

                </span>

            </div>

            <!-- Personal information -->
            <div class="student-profile__section">

                <h3>Personal Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>First Name</span>
                        <strong>{{ $teacher->first_name }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Last Name</span>
                        <strong>{{ $teacher->last_name }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Date of Birth</span>
                        <strong>
                            {{ $teacher->date_of_birth
                                ? $teacher->date_of_birth->format('d F Y')
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
                        <strong>{{ $teacher->email ?? 'Not provided' }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Phone Number</span>
                        <strong>{{ $teacher->phone ?? 'Not provided' }}</strong>
                    </div>

                </div>

            </div>

            <!-- Employment information -->
            <div class="student-profile__section">

                <h3>Employment Information</h3>

                <div class="student-profile__grid">

                    <div class="student-profile__field">
                        <span>Teacher Number</span>
                        <strong>{{ $teacher->teacher_number }}</strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Employment Date</span>
                        <strong>
                            {{ $teacher->employment_date->format('d F Y') }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Employment Status</span>
                        <strong>{{ ucfirst($teacher->status) }}</strong>
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
                            {{ $teacher->created_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                    <div class="student-profile__field">
                        <span>Last Updated</span>
                        <strong>
                            {{ $teacher->updated_at?->format('d F Y, h:i A') ?? 'Not available' }}
                        </strong>
                    </div>

                </div>

            </div>

        </section>

    </main>

</body>
</html>