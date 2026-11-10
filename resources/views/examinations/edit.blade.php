<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Examination | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container students-page">

        <header class="page-header">
            <div>
                <h1>Edit Examination</h1>
                <p>Update examination information.</p>
            </div>

            <a href="{{ route('examinations.show', $examination) }}"
               class="students-page__button">
                Back to Examination
            </a>
        </header>

        @if ($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('examinations.update', $examination) }}"
            class="form-container"
        >

            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="teaching_assignment_id">
                    Teaching Assignment
                </label>

                @if ($examination->results_count > 0)
                    <input type="hidden" name="teaching_assignment_id" value="{{ $examination->teaching_assignment_id }}">
                @endif

                <select
                    id="teaching_assignment_id"
                    name="teaching_assignment_id"
                    required
                    @disabled($examination->results_count > 0)
                >
                    @foreach ($teachingAssignments as $assignment)
                        <option
                            value="{{ $assignment->id }}"
                            @selected(
                                old(
                                    'teaching_assignment_id',
                                    $examination->teaching_assignment_id
                                ) == $assignment->id
                            )
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
            </div>

            <div class="form-group">
                <label for="title">Examination Title</label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title', $examination->title) }}"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-group">
                <label for="exam_type">Examination Type</label>

                <select id="exam_type" name="exam_type" required>
                    @foreach (\App\Models\Examination::EXAM_TYPES as $type)
                        <option
                            value="{{ $type }}"
                            @selected(
                                old('exam_type', $examination->exam_type) === $type
                            )
                        >
                            {{ ucwords(str_replace('_', ' ', $type)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="exam_date">Examination Date</label>

                <input
                    type="date"
                    id="exam_date"
                    name="exam_date"
                    value="{{ old(
                        'exam_date',
                        $examination->exam_date->format('Y-m-d')
                    ) }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="maximum_marks">Maximum Marks</label>

                <input
                    type="number"
                    id="maximum_marks"
                    name="maximum_marks"
                    value="{{ old('maximum_marks', $examination->maximum_marks) }}"
                    min="0.01"
                    max="999999.99"
                    step="0.01"
                    required
                >
            </div>

            <div class="form-group">
                <label for="pass_marks">Pass Marks</label>

                <input
                    type="number"
                    id="pass_marks"
                    name="pass_marks"
                    value="{{ old(
                        'pass_marks',
                        $examination->pass_marks
                    ) }}"
                    min="0"
                    max="999999.99"
                    step="0.01"
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    maxlength="5000"
                >{{ old('description', $examination->description) }}</textarea>
            </div>

            @if ($examination->results_count > 0)
                <p class="text-amber-600 text-sm mt-2">
                    This examination already contains results.
                    Its teaching assignment cannot be changed.
                </p>
            @endif

            <div class="form-actions">
                <button type="submit" class="students-page__button">
                    Save Changes
                </button>

                <a href="{{ route('examinations.show', $examination) }}">
                    Cancel
                </a>
            </div>

        </form>

    </main>

</body>
</html>