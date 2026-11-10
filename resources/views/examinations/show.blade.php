<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $examination->title }} | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container students-page">

        <header class="page-header">
            <div>
                <h1>{{ $examination->title }}</h1>
                <p>Examination details and student results.</p>
            </div>

            <div class="header-actions">

                <a href="{{ route('examinations.index') }}"
                   class="students-page__button">
                    Back to Examinations
                </a>

                @can('update', $examination)
                    <a href="{{ route('examinations.edit', $examination) }}"
                       class="students-page__button">
                        Edit Examination
                    </a>
                @endcan

            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="student-profile">

            <h2>Examination Information</h2>

            <div class="profile-grid">

                <div>
                    <strong>Examination Title</strong>
                    <p>{{ $examination->title }}</p>
                </div>

                <div>
                    <strong>Examination Type</strong>
                    <p>
                        {{ ucwords(str_replace('_', ' ', $examination->exam_type)) }}
                    </p>
                </div>

                <div>
                    <strong>Examination Date</strong>
                    <p>{{ $examination->exam_date->format('d M Y') }}</p>
                </div>

                <div>
                    <strong>Teacher</strong>

                    <p>
                        {{ $examination->teachingAssignment->teacher->teacher_number }}
                    </p>

                    <p>
                        {{ $examination->teachingAssignment->teacher->first_name }}
                        {{ $examination->teachingAssignment->teacher->last_name }}
                    </p>
                </div>

                <div>
                    <strong>Academic Class</strong>
                    <p>
                        {{ $examination->teachingAssignment->academicClass->name }}
                    </p>
                </div>

                <div>
                    <strong>Subject</strong>
                    <p>
                        {{ $examination->teachingAssignment->subject->name }}
                    </p>
                </div>

                <div>
                    <strong>Maximum Marks</strong>
                    <p>{{ number_format((float) $examination->maximum_marks, 2) }}</p>
                </div>

                <div>
                    <strong>Pass Marks</strong>
                    <p>
                        {{ $examination->pass_marks !== null
                            ? number_format((float) $examination->pass_marks, 2)
                            : 'Not specified' }}
                    </p>
                </div>

                <div>
                    <strong>Created By</strong>
                    <p>{{ $examination->creator->name }}</p>
                </div>

            </div>

            @if ($examination->description)
                <div>
                    <strong>Description</strong>
                    <p>{{ $examination->description }}</p>
                </div>
            @endif

        </section>

        <section class="page-section">

            <header class="page-header">
                <div>
                    <h2>Examination Results</h2>
                    <p>
                        Existing results and recorded grades.
                    </p>
                </div>
            </header>

            <div class="table-responsive">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Marks Obtained</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($examination->results as $result)

                            @php
                                $student = $result->studentEnrollment?->student;

                                $percentage = $examination->maximum_marks > 0
                                    ? (
                                        (float) $result->marks_obtained
                                        / (float) $examination->maximum_marks
                                    ) * 100
                                    : 0;

                                $hasPassed = $examination->pass_marks !== null
                                    ? (float) $result->marks_obtained
                                        >= (float) $examination->pass_marks
                                    : null;
                            @endphp

                            <tr>
                                <td>
                                    {{ $student?->student_number ?? 'Unavailable' }}
                                </td>

                                <td>
                                    {{ $student
                                        ? $student->first_name . ' ' . $student->last_name
                                        : 'Unavailable student' }}
                                </td>

                                <td>
                                    {{ number_format((float) $result->marks_obtained, 2) }}
                                    /
                                    {{ number_format((float) $examination->maximum_marks, 2) }}
                                </td>

                                <td>
                                    {{ number_format($percentage, 2) }}%
                                </td>

                                <td>
                                    <strong>{{ $result->grade }}</strong>

                                    @if ($hasPassed !== null)
                                        <span>
                                            {{ $hasPassed ? 'Passed' : 'Failed' }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $result->remarks ?: '—' }}
                                </td>

                                <td>
                                    @can('delete', $result)
                                        <form
                                            method="POST"
                                            action="{{ route('examination-results.destroy', $result) }}"
                                            onsubmit="return confirm('Are you sure you want to remove this examination result?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit">
                                                Remove
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="7">
                                    No examination results have been recorded.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>

        <section class="page-section">

            <header class="page-header">
                <div>
                    <h2>Record or Update Results</h2>
                    <p>
                        Enter marks for eligible students in this academic class.
                    </p>
                </div>
            </header>

            @if ($eligibleEnrollments->isEmpty())

                <p>
                    No eligible active student enrollments are currently available.
                </p>

            @else

                @can('update', $examination)

                    <form
                        method="POST"
                        action="{{ route('examinations.results.save', $examination) }}"
                    >

                        @csrf
                        @method('PUT')

                        <div class="table-responsive">

                            <table class="students-table">

                                <thead>
                                    <tr>
                                        <th>Student Number</th>
                                        <th>Student Name</th>
                                        <th>Marks Obtained</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @foreach ($eligibleEnrollments as $index => $enrollment)

                                        @php
                                            $existingResult = $examination->results
                                                ->firstWhere(
                                                    'student_enrollment_id',
                                                    $enrollment->id
                                                );

                                            $marksField = "results.{$index}.marks_obtained";
                                            $remarksField = "results.{$index}.remarks";
                                        @endphp

                                        <tr>

                                            <td>
                                                {{ $enrollment->student->student_number }}
                                            </td>

                                            <td>
                                                {{ $enrollment->student->first_name }}
                                                {{ $enrollment->student->last_name }}
                                            </td>

                                            <td>

                                                <input
                                                    type="hidden"
                                                    name="results[{{ $index }}][student_enrollment_id]"
                                                    value="{{ $enrollment->id }}"
                                                >

                                                <input
                                                    type="number"
                                                    name="results[{{ $index }}][marks_obtained]"
                                                    value="{{ old(
                                                        $marksField,
                                                        $existingResult?->marks_obtained
                                                    ) }}"
                                                    min="0"
                                                    max="{{ $examination->maximum_marks }}"
                                                    step="0.01"
                                                    required
                                                >

                                            </td>

                                            <td>
                                                <input
                                                    type="text"
                                                    name="results[{{ $index }}][remarks]"
                                                    value="{{ old(
                                                        $remarksField,
                                                        $existingResult?->remarks
                                                    ) }}"
                                                    maxlength="2000"
                                                >
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                        <div class="form-actions">
                            <button type="submit" class="students-page__button">
                                Save Examination Results
                            </button>
                        </div>

                    </form>

                @endcan

            @endif

        </section>

        @can('delete', $examination)

            @if ($examination->results->isEmpty())

                <section class="page-section">

                    <h2>Remove Examination</h2>

                    <p>
                        This examination currently has no recorded results.
                        It can be removed by an administrator.
                    </p>

                    <form
                        method="POST"
                        action="{{ route('examinations.destroy', $examination) }}"
                        onsubmit="return confirm('Are you sure you want to permanently remove this examination?')"
                    >
                        @csrf
                        @method('DELETE')

                        <button type="submit">
                            Remove Examination
                        </button>
                    </form>

                </section>

            @endif

        @endcan

    </main>

</body>
</html>