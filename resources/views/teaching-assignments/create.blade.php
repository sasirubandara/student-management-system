<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Teaching Assignment | Student Management System</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <h1>Add Teaching Assignment</h1>
            <p>Assign a teacher to a subject within an academic class.</p>
        </header>

        <a href="{{ route('teaching-assignments.index') }}"
           class="students-page__button students-page__button--secondary">
            Back to Teaching Assignments
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

        <form action="{{ route('teaching-assignments.store') }}" method="POST">

            @csrf

            <div class="form-grid">

                {{-- Teacher --}}
                <div class="form-group">

                    <label for="teacher_id">Teacher *</label>

                    <select id="teacher_id" name="teacher_id" required>

                        <option value="">Select a teacher</option>

                        @foreach ($teachers as $teacher)
                            <option
                                value="{{ $teacher->id }}"
                                @selected(old('teacher_id') == $teacher->id)
                            >
                                {{ $teacher->teacher_number }} -
                                {{ $teacher->first_name }}
                                {{ $teacher->last_name }}
                            </option>
                        @endforeach

                    </select>

                    @error('teacher_id')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Academic Class --}}
                <div class="form-group">

                    <label for="academic_class_id">Academic Class *</label>

                    <select id="academic_class_id"
                            name="academic_class_id"
                            required>

                        <option value="">Select an academic class</option>

                        @foreach ($academicClasses as $academicClass)
                            <option
                                value="{{ $academicClass->id }}"
                                @selected(old('academic_class_id') == $academicClass->id)
                            >
                                {{ $academicClass->name }}
                                ({{ $academicClass->code }})
                            </option>
                        @endforeach

                    </select>

                    @error('academic_class_id')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

                {{-- Subject --}}
                <div class="form-group">

                    <label for="subject_id">Subject *</label>

                    <select id="subject_id"
                            name="subject_id"
                            required
                            disabled>

                        <option value="">Select an academic class first</option>

                        @foreach ($academicClasses as $academicClass)
                            @foreach ($academicClass->subjects as $subject)
                                <option
                                    value="{{ $subject->id }}"
                                    data-class-id="{{ $academicClass->id }}"
                                    @selected(
                                        old('academic_class_id') == $academicClass->id
                                        && old('subject_id') == $subject->id
                                    )
                                >
                                    {{ $subject->name }}
                                    ({{ $subject->code }})
                                </option>
                            @endforeach
                        @endforeach

                    </select>

                    @error('subject_id')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                </div>

            </div>

            <div class="form-actions">

                <button type="submit">
                    Create Assignment
                </button>

                <a href="{{ route('teaching-assignments.index') }}"
                   class="students-page__button students-page__button--secondary">
                    Cancel
                </a>

            </div>

        </form>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const classSelect = document.getElementById('academic_class_id');
            const subjectSelect = document.getElementById('subject_id');

            function updateSubjects() {

                const selectedClassId = classSelect.value;

                let hasSubjects = false;
                let selectedSubjectAvailable = false;

                Array.from(subjectSelect.options).forEach(function (option) {

                    if (!option.dataset.classId) {
                        return;
                    }

                    const belongsToSelectedClass =
                        option.dataset.classId === selectedClassId;

                    option.hidden = !belongsToSelectedClass;
                    option.disabled = !belongsToSelectedClass;

                    if (belongsToSelectedClass) {
                        hasSubjects = true;

                        if (option.selected) {
                            selectedSubjectAvailable = true;
                        }
                    }

                });

                subjectSelect.disabled = !selectedClassId || !hasSubjects;

                if (!selectedClassId) {

                    subjectSelect.options[0].text =
                        'Select an academic class first';

                    subjectSelect.value = '';

                } else if (!hasSubjects) {

                    subjectSelect.options[0].text =
                        'No subjects assigned to this class';

                    subjectSelect.value = '';

                } else {

                    subjectSelect.options[0].text = 'Select a subject';

                    if (!selectedSubjectAvailable) {
                        subjectSelect.value = '';
                    }

                }

            }

            classSelect.addEventListener('change', updateSubjects);

            updateSubjects();

        });
    </script>

</body>
</html>