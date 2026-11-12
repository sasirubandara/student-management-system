<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
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
        $expectedDirectory = 'students/'.$student->getKey().'/';

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
        StudentDocument $document,
        AuditLogger $auditLogger
    ) {
        // The existing StudentPolicy restricts this ability to administrators.
        Gate::authorize('delete', $student);

        $documentId = $document->getKey();

        // Delete the database record and create its audit entry together.
        $filePath = DB::transaction(function () use (
            $student,
            $documentId,
            $auditLogger
        ) {
            $scopedDocument = $student->documents()
                ->whereKey($documentId)
                ->lockForUpdate()
                ->firstOrFail();

            // Ensure the stored path belongs to this student's directory.
            $expectedDirectory = 'students/'.$student->getKey().'/';

            abort_unless(
                Str::startsWith(
                    $scopedDocument->file_path,
                    $expectedDirectory
                ),
                404
            );

            $path = $scopedDocument->file_path;

            // Preserve only non-sensitive metadata for the audit record.
            $auditProperties = [
                'student_id' => $student->getKey(),
                'category' => $scopedDocument->category,
                'mime_type' => $scopedDocument->mime_type,
                'file_size' => $scopedDocument->file_size,
                'changed_fields' => ['deleted'],
            ];

            $scopedDocument->delete();

            $auditLogger->record(
                'student.document_deleted',
                $scopedDocument,
                $auditProperties
            );

            return $path;
        });

        // Remove the physical file after the database transaction commits.
        $disk = Storage::disk('student_documents');

        try {
            if ($disk->exists($filePath)) {
                $deleted = $disk->delete($filePath);

                if (! $deleted && $disk->exists($filePath)) {
                    throw new RuntimeException(
                        'The private document file could not be deleted.'
                    );
                }
            }
        } catch (Throwable $exception) {
            // The database record and audit entry are already committed.
            // Keep the remaining file private and log the cleanup failure.
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
    public function store(
        Request $request,
        Student $student,
        AuditLogger $auditLogger,
        NotificationService $notificationService
    ) {
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

        $actor = $request->user();

        // Generate a unique filename rather than trusting the original name.
        $filename = Str::uuid()->toString().'.'.$extension;

        // Keep each student's documents in a separate directory.
        $directory = 'students/'.$student->getKey();

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

            // Save document metadata and its audit entry together.
            DB::transaction(function () use (
                $student,
                $actor,
                $validated,
                $file,
                $storedPath,
                $mimeType,
                $auditLogger,
                $notificationService
            ): void {
                $document = $student->documents()->create([
                    'uploaded_by' => $actor->getKey(),

                    'title' => $validated['title'],

                    'category' => $validated['category'],

                    'original_name' => $this->safeOriginalName($file),

                    'file_path' => $storedPath,

                    'mime_type' => $mimeType,

                    'file_size' => $file->getSize(),

                    'description' => $validated['description'] ?? null,
                ]);

                $auditLogger->record(
                    'student.document_uploaded',
                    $document,
                    [
                        'student_id' => $student->getKey(),
                        'category' => $document->category,
                        'mime_type' => $document->mime_type,
                        'file_size' => $document->file_size,
                    ]
                );

                // Notify other active users about the document upload.
                $notificationService->notifyOtherActiveUsers(
                    actor: $actor,
                    event: 'student.document_uploaded',
                    title: 'Student document uploaded',
                    message: 'A new '
                        .$document->category
                        .' document titled "'
                        .$document->title
                        .'" was uploaded for '
                        .$student->first_name
                        .' '
                        .$student->last_name
                        .'.',
                    url: route('students.documents.index', $student),
                    icon: 'file-up',
                    recipientRoles: [
                        User::ROLE_ADMIN,
                        User::ROLE_STAFF,
                    ],
                );
            });
        } catch (Throwable $exception) {
            // Clean up the uploaded file if the database operation fails.
            if (is_string($storedPath)) {
                try {
                    $disk = Storage::disk('student_documents');

                    if ($disk->exists($storedPath)) {
                        $deleted = $disk->delete($storedPath);

                        if (! $deleted && $disk->exists($storedPath)) {
                            throw new RuntimeException(
                                'The uploaded document could not be cleaned up.'
                            );
                        }
                    }
                } catch (Throwable $cleanupException) {
                    Log::warning('Student document upload cleanup failed.', [
                        'student_id' => $student->getKey(),
                        'file_path' => $storedPath,
                        'exception' => $cleanupException->getMessage(),
                    ]);
                }
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
