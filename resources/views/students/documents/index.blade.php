<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Documents</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

    <x-authenticated-navigation />

    <main class="page-container">

        <header class="page-header">
            <div>
                <h1>Student Documents</h1>

                <p>
                    Manage documents for
                    {{ $student->first_name }}
                    {{ $student->last_name }}
                </p>

                <p>
                    Student Number: {{ $student->student_number }}
                </p>
            </div>

            <div class="page-header__actions">
                <a
                    href="{{ route('students.show', $student) }}"
                    class="students-page__button"
                >
                    Back to Student Profile
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning" role="alert">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Please correct the following errors:</strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Upload form --}}
        @can('create', \App\Models\Student::class)

            <section class="page-section">

                <h2>Upload a Document</h2>

                <p>
                    Accepted formats: PDF, JPG, JPEG, and PNG.
                    Maximum file size: 5 MB.
                </p>

                <form
                    action="{{ route('students.documents.store', $student) }}"
                    method="POST"
                    enctype="multipart/form-data"
                >
                    @csrf

                    <div class="form-group">
                        <label for="title">Document Title</label>

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
                        <label for="category">Document Category</label>

                        <select
                            id="category"
                            name="category"
                            required
                        >
                            <option value="">Select a category</option>

                            <option
                                value="academic"
                                @selected(old('category') === 'academic')
                            >
                                Academic
                            </option>

                            <option
                                value="identification"
                                @selected(old('category') === 'identification')
                            >
                                Identification
                            </option>

                            <option
                                value="consent"
                                @selected(old('category') === 'consent')
                            >
                                Consent
                            </option>

                            <option
                                value="other"
                                @selected(old('category') === 'other')
                            >
                                Other
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>

                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            maxlength="2000"
                        >{{ old('description') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="file">Select Document</label>

                        <input
                            type="file"
                            id="file"
                            name="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="students-page__button"
                    >
                        Upload Document
                    </button>

                </form>

            </section>

        @endcan

        {{-- Existing documents --}}
        <section class="page-section">

            <h2>Uploaded Documents</h2>

            @if ($documents->count() > 0)

                <div class="table-responsive">

                    <table class="students-table">

                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Original Filename</th>
                                <th>Uploaded By</th>
                                <th>File Size</th>
                                <th>Upload Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach ($documents as $document)

                                <tr>
                                    <td>{{ $document->title }}</td>

                                    <td>
                                        {{ ucfirst($document->category) }}
                                    </td>

                                    <td>
                                        {{ $document->original_name }}
                                    </td>

                                    <td>
                                        {{ $document->uploadedBy?->name ?? 'Former user' }}
                                    </td>

                                    <td>
                                        {{ number_format($document->file_size / 1024, 1) }}
                                        KB
                                    </td>

                                    <td>
                                        {{ $document->created_at->format('Y-m-d H:i') }}
                                    </td>

                                    <td>
                                        <a
                                            href="{{ route(
                                                'students.documents.download',
                                                [
                                                    'student' => $student,
                                                    'document' => $document
                                                ]
                                            ) }}"
                                        >
                                            Download
                                        </a>

                                        @can('delete', $student)

                                            <form
                                                action="{{ route(
                                                    'students.documents.destroy',
                                                    [
                                                        'student' => $student,
                                                        'document' => $document
                                                    ]
                                                ) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this document?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit">
                                                    Delete
                                                </button>
                                            </form>

                                        @endcan
                                    </td>
                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                {{ $documents->links() }}

            @else

                <p>No documents have been uploaded for this student yet.</p>

            @endif

        </section>

    </main>

</body>
</html>