<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Examination | Student Management System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container students-page">

        <header class="page-header">
            <div>
                <h1>Create Examination</h1>
                <p>Create an examination for an assigned class and subject.</p>
            </div>

            <a href="{{ route('examinations.index') }}"
               class="students-page__button">
                Back to Examinations
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

        @if ($teachingAssignments->isEmpty())

            <p>
                No active teaching assignments are currently available.
                Create an active teaching assignment before scheduling an examination.
            </p>

        @else

            <form
                method="POST"
                action="{{ route('examinations.store') }}"
                class="form-container"
            >

                @csrf

                <div class="form-group">
                    <label for="teaching_assignment_id">
                        Teaching Assignment
                    </label>

                    <select
                        id="teaching_assignment_id"
                        name="teaching_assignment_id"
                        required
                    >
                        <option value="">Select a teaching assignment</option>

                        @foreach ($teachingAssignments as $assignment)
                            <option
                                value="{{ $assignment->id }}"
                                @selected(old('teaching_assignment_id') == $assignment->id)
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
                        value="{{ old('title') }}"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="exam_type">Examination Type</label>

                    <select id="exam_type" name="exam_type" required>
                        <option value="">Select examination type</option>

                        @foreach (\App\Models\Examination::EXAM_TYPES as $type)
                            <option
                                value="{{ $type }}"
                                @selected(old('exam_type') === $type)
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
                        value="{{ old('exam_date') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="maximum_marks">Maximum Marks</label>

                    <input
                        type="number"
                        id="maximum_marks"
                        name="maximum_marks"
                        value="{{ old('maximum_marks', '100.00') }}"
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
                        value="{{ old('pass_marks', '40.00') }}"
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
                    >{{ old('description') }}</textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="students-page__button">
                        Create Examination
                    </button>

                    <a href="{{ route('examinations.index') }}">
                        Cancel
                    </a>
                </div>

            </form>

        @endif

    </main>

</body>
</html>