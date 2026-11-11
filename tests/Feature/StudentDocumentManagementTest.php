<?php

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function makeStudentDocumentTestUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
    ]);
}

function createStudentDocumentTestRecord(
    Student $student,
    User $user
): StudentDocument {
    $path = 'students/' . $student->getKey()
        . '/' . Str::uuid() . '.pdf';

    Storage::disk('student_documents')->put(
        $path,
        '%PDF-1.4 test document content'
    );

    return $student->documents()->create([
        'uploaded_by' => $user->getKey(),
        'title' => 'Student Transcript',
        'category' => 'academic',
        'original_name' => 'student-transcript.pdf',
        'file_path' => $path,
        'mime_type' => 'application/pdf',
        'file_size' => 32,
        'description' => 'Academic transcript test document',
    ]);
}

beforeEach(function () {
    Storage::fake('student_documents');
});

it('allows authorized users to view student documents', function () {
    $staff = makeStudentDocumentTestUser('staff');

    $student = Student::factory()->create();

    $document = createStudentDocumentTestRecord(
        $student,
        $staff
    );

    $response = $this->actingAs($staff)
        ->get(route('students.documents.index', $student));

    $response->assertOk();

    $response->assertSee('Student Transcript');
    $response->assertSee('student-transcript.pdf');
});

it('allows staff to upload student documents', function () {
    $staff = makeStudentDocumentTestUser('staff');

    $student = Student::factory()->create();

    $file = UploadedFile::fake()->create(
        'academic-record.pdf',
        100,
        'application/pdf'
    );

    $response = $this->actingAs($staff)
        ->post(
            route('students.documents.store', $student),
            [
                'title' => 'Academic Record',
                'category' => 'academic',
                'description' => 'Test academic document',
                'file' => $file,
            ]
        );

    $response->assertRedirect(
        route('students.documents.index', $student)
    );

    $document = StudentDocument::where(
        'student_id',
        $student->getKey()
    )->firstOrFail();

    expect($document->uploaded_by)->toBe($staff->getKey());

    expect($document->original_name)
        ->toBe('academic-record.pdf');

    Storage::disk('student_documents')
        ->assertExists($document->file_path);

    $auditLog = AuditLog::query()
        ->where('event', 'student.document_uploaded')
        ->where('auditable_type', $document->getMorphClass())
        ->where('auditable_id', $document->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($staff->getKey());

    expect($auditLog->properties['student_id'])
        ->toBe($student->getKey());

    expect($auditLog->properties['category'])->toBe('academic');
    expect($auditLog->properties['mime_type'])->toBe('application/pdf');

    expect(array_keys($auditLog->properties))
        ->toEqualCanonicalizing([
            'student_id',
            'category',
            'mime_type',
            'file_size',
        ]);
});

it('rejects unsupported document file types', function () {
    $staff = makeStudentDocumentTestUser('staff');

    $student = Student::factory()->create();

    $file = UploadedFile::fake()->create(
        'document.txt',
        100,
        'text/plain'
    );

    $response = $this->actingAs($staff)
        ->post(
            route('students.documents.store', $student),
            [
                'title' => 'Unsupported Document',
                'category' => 'other',
                'file' => $file,
            ]
        );

    $response->assertSessionHasErrors('file');

    expect(
        StudentDocument::where('student_id', $student->getKey())->count()
    )->toBe(0);

    expect(
        Storage::disk('student_documents')->allFiles()
    )->toBeEmpty();

    expect(
        AuditLog::query()
            ->where('event', 'student.document_uploaded')
            ->exists()
    )->toBeFalse();
});

it('rejects documents larger than 5 MB', function () {
    $staff = makeStudentDocumentTestUser('staff');

    $student = Student::factory()->create();

    $file = UploadedFile::fake()->create(
        'large-document.pdf',
        5121,
        'application/pdf'
    );

    $response = $this->actingAs($staff)
        ->post(
            route('students.documents.store', $student),
            [
                'title' => 'Large Document',
                'category' => 'academic',
                'file' => $file,
            ]
        );

    $response->assertSessionHasErrors('file');

    expect(
        StudentDocument::where('student_id', $student->getKey())->count()
    )->toBe(0);
});

it('allows authorized users to download student documents', function () {
    $staff = makeStudentDocumentTestUser('staff');

    $student = Student::factory()->create();

    $document = createStudentDocumentTestRecord(
        $student,
        $staff
    );

    $response = $this->actingAs($staff)->get(
        route('students.documents.download', [
            'student' => $student,
            'document' => $document,
        ])
    );

    $response->assertDownload('student-transcript.pdf');
});

it('prevents downloading another students document', function () {
    $staff = makeStudentDocumentTestUser('staff');

    $studentOne = Student::factory()->create();

    $studentTwo = Student::factory()->create();

    $document = createStudentDocumentTestRecord(
        $studentTwo,
        $staff
    );

    $response = $this->actingAs($staff)->get(
        route('students.documents.download', [
            'student' => $studentOne,
            'document' => $document,
        ])
    );

    $response->assertNotFound();
});

it('returns not found when a document file is missing', function () {
    $staff = makeStudentDocumentTestUser('staff');

    $student = Student::factory()->create();

    $document = $student->documents()->create([
        'uploaded_by' => $staff->getKey(),
        'title' => 'Missing Document',
        'category' => 'academic',
        'original_name' => 'missing-document.pdf',
        'file_path' => 'students/' . $student->getKey() . '/missing.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 100,
    ]);

    $response = $this->actingAs($staff)->get(
        route('students.documents.download', [
            'student' => $student,
            'document' => $document,
        ])
    );

    $response->assertNotFound();
});

it('allows administrators to delete student documents', function () {
    $admin = makeStudentDocumentTestUser('admin');

    $student = Student::factory()->create();

    $document = createStudentDocumentTestRecord(
        $student,
        $admin
    );

    $path = $document->file_path;

    Storage::disk('student_documents')->assertExists($path);

    $response = $this->actingAs($admin)->delete(
        route('students.documents.destroy', [
            'student' => $student,
            'document' => $document,
        ])
    );

    $response->assertRedirect(
        route('students.documents.index', $student)
    );

    $response->assertSessionHas(
        'success',
        'Student document deleted successfully.'
    );

    $this->assertDatabaseMissing('student_documents', [
        'id' => $document->getKey(),
    ]);

    Storage::disk('student_documents')->assertMissing($path);

    $auditLog = AuditLog::query()
        ->where('event', 'student.document_deleted')
        ->where('auditable_type', $document->getMorphClass())
        ->where('auditable_id', $document->getKey())
        ->firstOrFail();

    expect($auditLog->user_id)->toBe($admin->getKey());

    expect($auditLog->properties['student_id'])
        ->toBe($student->getKey());
});

it('prevents staff from deleting student documents', function () {
    $admin = makeStudentDocumentTestUser('admin');

    $staff = makeStudentDocumentTestUser('staff');

    $student = Student::factory()->create();

    $document = createStudentDocumentTestRecord(
        $student,
        $admin
    );

    $response = $this->actingAs($staff)->delete(
        route('students.documents.destroy', [
            'student' => $student,
            'document' => $document,
        ])
    );

    $response->assertForbidden();

    $this->assertDatabaseHas('student_documents', [
        'id' => $document->getKey(),
    ]);

    Storage::disk('student_documents')
        ->assertExists($document->file_path);
});

it('prevents deleting another students document', function () {
    $admin = makeStudentDocumentTestUser('admin');

    $studentOne = Student::factory()->create();

    $studentTwo = Student::factory()->create();

    $document = createStudentDocumentTestRecord(
        $studentTwo,
        $admin
    );

    $response = $this->actingAs($admin)->delete(
        route('students.documents.destroy', [
            'student' => $studentOne,
            'document' => $document,
        ])
    );

    $response->assertNotFound();

    $this->assertDatabaseHas('student_documents', [
        'id' => $document->getKey(),
    ]);

    Storage::disk('student_documents')
        ->assertExists($document->file_path);
});