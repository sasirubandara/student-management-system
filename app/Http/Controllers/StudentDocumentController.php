<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class StudentDocumentController extends Controller
{
    /**
     * Display the documents belonging to a student.
     */
    public function index(Student $student)
    {
        Gate::authorize('view', $student);

        $documents = $student->documents()
            ->with('uploadedBy')
            ->latest()
            ->paginate(15);

        return view('students.documents.index', compact(
            'student',
            'documents'
        ));
    }

    /**
     * Download a document belonging to a student.
     */
    public function download(
        Student $student,
        StudentDocument $document
    ) {
        Gate::authorize('view', $student);

        // Ensure this document belongs to the requested student.
        $document = $student->documents()
            ->whereKey($document->getKey())
            ->firstOrFail();

        $disk = Storage::disk('student_documents');

        // Ensure the stored path belongs to this student's directory.
        $expectedDirectory = 'students/' . $student->getKey() . '/';

        abort_unless(
            Str::startsWith($document->file_path, $expectedDirectory),
            404
        );

        // Return 404 if the physical file is missing.
        abort_unless(
            $disk->exists($document->file_path),
            404
        );

        // Force download instead of displaying the file in the browser.
        return $disk->download(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Delete a student document.
     *
     * Only administrators authorized to delete student records
     * may delete student documents.
     */
    public function destroy(
        Student $student,
        StudentDocument $document
    ) {
        // The existing StudentPolicy restricts this ability to administrators.
        Gate::authorize('delete', $student);

        $documentId = $document->getKey();

        // Delete the database record inside a transaction.
        // Lock the record to prevent concurrent deletion.
        $filePath = DB::transaction(function () use (
            $student,
            $documentId
        ) {
            $scopedDocument = $student->documents()
                ->whereKey($documentId)
                ->lockForUpdate()
                ->firstOrFail();

            // Ensure the stored path belongs to this student's directory.
            $expectedDirectory = 'students/' . $student->getKey() . '/';

            abort_unless(
                Str::startsWith(
                    $scopedDocument->file_path,
                    $expectedDirectory
                ),
                404
            );

            $path = $scopedDocument->file_path;

            $scopedDocument->delete();

            return $path;
        });

        // Remove the physical file after the database transaction commits.
        $disk = Storage::disk('student_documents');

        try {
            if ($disk->exists($filePath)) {
                $deleted = $disk->delete($filePath);

                if (!$deleted && $disk->exists($filePath)) {
                    throw new \RuntimeException(
                        'The private document file could not be deleted.'
                    );
                }
            }
        } catch (\Throwable $exception) {
            // The database record is already deleted.
            // Log the failure because the remaining file is still private.
            Log::warning('Student document file cleanup failed.', [
                'student_document_id' => $documentId,
                'student_id' => $student->getKey(),
                'exception' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('students.documents.index', $student)
                ->with(
                    'warning',
                    'The document record was deleted, but its stored file could not be removed. Please contact an administrator.'
                );
        }

        return redirect()
            ->route('students.documents.index', $student)
            ->with('success', 'Student document deleted successfully.');
    }

    /**
     * Store a document belonging to a student.
     */
    public function store(Request $request, Student $student)
    {
        // The user must be authorized to view this student.
        Gate::authorize('view', $student);

        // The user must also have permission to create student records.
        Gate::authorize('create', Student::class);

        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'category' => [
                'required',
                'string',
                'in:academic,identification,consent,other',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];

        // Determine the file extension from its detected MIME type.
        $mimeType = $file->getMimeType();

        $extension = match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',

            default => throw ValidationException::withMessages([
                'file' => 'The uploaded document must be a PDF, JPG, JPEG, or PNG file.',
            ]),
        };

        // Generate a unique filename rather than trusting the original name.
        $filename = Str::uuid()->toString() . '.' . $extension;

        // Keep each student's documents in a separate directory.
        $directory = 'students/' . $student->getKey();

        $storedPath = null;

        try {
            // Store the file using the private student documents disk.
            $storedPath = $file->storeAs(
                $directory,
                $filename,
                'student_documents'
            );

            if ($storedPath === false) {
                throw new RuntimeException(
                    'The document could not be stored.'
                );
            }

            // Save document metadata in the database.
            DB::transaction(function () use (
                $student,
                $request,
                $validated,
                $file,
                $storedPath,
                $mimeType
            ) {
                $student->documents()->create([
                    'uploaded_by' => $request->user()->getKey(),

                    'title' => $validated['title'],

                    'category' => $validated['category'],

                    'original_name' => $this->safeOriginalName($file),

                    'file_path' => $storedPath,

                    'mime_type' => $mimeType,

                    'file_size' => $file->getSize(),

                    'description' => $validated['description'] ?? null,
                ]);
            });
        } catch (Throwable $exception) {
            // Remove the stored file if saving its database record fails.
            if (is_string($storedPath)) {
                Storage::disk('student_documents')->delete($storedPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('students.documents.index', $student)
            ->with('success', 'Student document uploaded successfully.');
    }

    /**
     * Return a safe version of the original filename for display.
     */
    private function safeOriginalName(UploadedFile $file): string
    {
        $filename = basename(
            str_replace('\\', '/', $file->getClientOriginalName())
        );

        // Remove control characters from the uploaded filename.
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename) ?? '';

        $filename = trim($filename);

        if ($filename === '') {
            $filename = 'document';
        }

        return mb_substr($filename, 0, 255);
    }
}