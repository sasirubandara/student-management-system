<?php

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Create an administrator or staff test account.
 */
function makeStudentCsvTestUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
    ]);
}

/**
 * Create a fake CSV upload.
 */
function makeStudentCsvTestFile(string $contents): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'students.csv',
        $contents
    );
}

// Import page and template

it('allows administrators to view the student CSV import page', function (): void {
    $admin = makeStudentCsvTestUser(User::ROLE_ADMIN);

    $response = $this->actingAs($admin)
        ->get(route('students.import.create'));

    $response->assertOk()
        ->assertSee('Import Students from CSV')
        ->assertSee('Download CSV Template');
});

it('allows staff users to view the student CSV import page', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $response = $this->actingAs($staff)
        ->get(route('students.import.create'));

    $response->assertOk()
        ->assertSee('Import Students from CSV');
});

it('downloads the student CSV import template', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $response = $this->actingAs($staff)
        ->get(route('students.import.template'));

    $response->assertOk()
        ->assertDownload('student-import-template.csv');

    $content = $response->streamedContent();

    expect(ltrim($content, "\xEF\xBB\xBF"))
        ->toStartWith(
            'student_number,first_name,last_name,email,phone,date_of_birth,enrollment_date,status'
        );
});

// Student CSV import

it('imports valid student records from a CSV file', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $csv = implode("\n", [
        'student_number,first_name,last_name,email,phone,date_of_birth,enrollment_date,status',
        'STU-CSV-001,Alex,Perera,alex.csv@example.test,0771234567,2005-06-15,2026-01-15,active',
        'STU-CSV-002,Sarah,Fernando,sarah.csv@example.test,0779876543,2006-03-20,2026-01-15,inactive',
    ]);

    $response = $this->actingAs($staff)
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 2
                && $result['failed'] === 0
                && $result['errors'] === [];
        });

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-CSV-001',
        'first_name' => 'Alex',
        'last_name' => 'Perera',
        'email' => 'alex.csv@example.test',
        'status' => Student::STATUS_ACTIVE,
    ]);

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-CSV-002',
        'first_name' => 'Sarah',
        'status' => Student::STATUS_INACTIVE,
    ]);
});

it('defaults an empty student status to active during CSV import', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $csv = implode("\n", [
        'student_number,first_name,last_name,email,phone,date_of_birth,enrollment_date,status',
        'STU-CSV-DEFAULT,Michael,Silva,,,2005-11-10,2026-02-01,',
    ]);

    $response = $this->actingAs($staff)
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 1
                && $result['failed'] === 0;
        });

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-CSV-DEFAULT',
        'status' => Student::STATUS_ACTIVE,
        'email' => null,
        'phone' => null,
    ]);
});

it('supports CSV files without optional columns', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $csv = implode("\n", [
        'student_number,first_name,last_name,enrollment_date',
        'STU-CSV-MINIMAL,Daniel,Silva,2026-03-01',
    ]);

    $response = $this->actingAs($staff)
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 1
                && $result['failed'] === 0;
        });

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-CSV-MINIMAL',
        'status' => Student::STATUS_ACTIVE,
        'email' => null,
        'phone' => null,
        'date_of_birth' => null,
    ]);
});

it('imports valid CSV rows and reports invalid and duplicate rows', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    Student::factory()->create([
        'student_number' => 'STU-CSV-EXISTING',
    ]);

    $csv = implode("\n", [
        'student_number,first_name,last_name,email,phone,date_of_birth,enrollment_date,status',
        'STU-CSV-GOOD,Alex,Perera,good.csv@example.test,0771234567,2005-06-15,2026-01-15,active',
        'STU-CSV-BAD,Sarah,Fernando,not-an-email,,2006-03-20,2026-01-15,active',
        'STU-CSV-EXISTING,Michael,Silva,michael@example.test,,2005-11-10,2026-02-01,active',
    ]);

    $response = $this->actingAs($staff)
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 1
                && $result['failed'] === 2
                && count($result['errors']) === 2;
        });

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-CSV-GOOD',
    ]);

    $this->assertDatabaseMissing('students', [
        'student_number' => 'STU-CSV-BAD',
    ]);
});

it('prevents importing a student number belonging to an archived student', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $archivedStudent = Student::factory()->create([
        'student_number' => 'STU-CSV-ARCHIVED',
    ]);

    $archivedStudent->delete();

    $csv = implode("\n", [
        'student_number,first_name,last_name,enrollment_date',
        'STU-CSV-ARCHIVED,Archived,Student,2026-01-15',
    ]);

    $response = $this->actingAs($staff)
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 0
                && $result['failed'] === 1
                && count($result['errors']) === 1;
        });

    $this->assertSoftDeleted('students', [
        'id' => $archivedStudent->id,
    ]);
});

it('rejects a CSV file with missing required header columns', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $csv = implode("\n", [
        'student_number,first_name,last_name',
        'STU-CSV-HEADER,Alex,Perera',
    ]);

    $response = $this->actingAs($staff)
        ->from(route('students.import.create'))
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHasErrors('file');

    $this->assertDatabaseMissing('students', [
        'student_number' => 'STU-CSV-HEADER',
    ]);
});

it('rejects unsupported CSV header columns', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $csv = implode("\n", [
        'student_number,first_name,last_name,enrollment_date,admin_role',
        'STU-CSV-UNKNOWN,Alex,Perera,2026-01-15,administrator',
    ]);

    $response = $this->actingAs($staff)
        ->from(route('students.import.create'))
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHasErrors('file');
});

it('skips malformed CSV rows and continues importing valid rows', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $csv = implode("\n", [
        'student_number,first_name,last_name,enrollment_date',
        'STU-CSV-VALID-ROW,Alex,Perera,2026-01-15',
        'STU-CSV-MALFORMED,Sarah',
        'STU-CSV-VALID-ROW-2,Michael,Silva,2026-02-01',
    ]);

    $response = $this->actingAs($staff)
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile($csv),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHas('import_result', function (array $result): bool {
            return $result['imported'] === 2
                && $result['failed'] === 1;
        });

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-CSV-VALID-ROW',
    ]);

    $this->assertDatabaseHas('students', [
        'student_number' => 'STU-CSV-VALID-ROW-2',
    ]);

    $this->assertDatabaseMissing('students', [
        'student_number' => 'STU-CSV-MALFORMED',
    ]);
});

it('rejects an empty CSV file without importing students', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    $response = $this->actingAs($staff)
        ->from(route('students.import.create'))
        ->post(route('students.import.store'), [
            'file' => makeStudentCsvTestFile(''),
        ]);

    $response->assertRedirect(route('students.import.create'))
        ->assertSessionHasErrors('file');

    $this->assertDatabaseCount('students', 0);
});

// Student CSV export

it('allows staff users to export active student records', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    Student::factory()->create([
        'student_number' => 'STU-CSV-EXPORT-001',
    ]);

    $response = $this->actingAs($staff)
        ->get(route('students.export'));

    $response->assertOk()
        ->assertDownload();

    $content = $response->streamedContent();

    expect($content)
        ->toContain('student_number')
        ->toContain('STU-CSV-EXPORT-001');
});

it('exports only students matching the current search query', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    Student::factory()->create([
        'student_number' => 'STU-CSV-MATCH-001',
        'first_name' => 'SearchMatch',
    ]);

    Student::factory()->create([
        'student_number' => 'STU-CSV-NOMATCH-002',
        'first_name' => 'DifferentStudent',
    ]);

    $response = $this->actingAs($staff)
        ->get(route('students.export', [
            'search' => 'SearchMatch',
        ]));

    $response->assertOk()
        ->assertDownload();

    $content = $response->streamedContent();

    expect($content)
        ->toContain('STU-CSV-MATCH-001')
        ->not->toContain('STU-CSV-NOMATCH-002');
});

it('excludes archived students from CSV exports', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    Student::factory()->create([
        'student_number' => 'STU-CSV-ACTIVE-001',
    ]);

    $archivedStudent = Student::factory()->create([
        'student_number' => 'STU-CSV-ARCHIVED-002',
    ]);

    $archivedStudent->delete();

    $response = $this->actingAs($staff)
        ->get(route('students.export'));

    $content = $response->streamedContent();

    expect($content)
        ->toContain('STU-CSV-ACTIVE-001')
        ->not->toContain('STU-CSV-ARCHIVED-002');
});

it('protects exported CSV cells against spreadsheet formula injection', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    Student::factory()->create([
        'student_number' => 'STU-CSV-FORMULA-001',
        'first_name' => '=HYPERLINK("https://example.invalid","Open")',
        'last_name' => '+SUM(1,2)',
    ]);

    $response = $this->actingAs($staff)
        ->get(route('students.export'));

    $content = $response->streamedContent();

    expect($content)
        ->toContain("'=HYPERLINK")
        ->toContain("'+SUM");
});

it('preserves ordinary international phone numbers during CSV export', function (): void {
    $staff = makeStudentCsvTestUser(User::ROLE_STAFF);

    Student::factory()->create([
        'student_number' => 'STU-CSV-PHONE-001',
        'phone' => '+94771234567',
    ]);

    $response = $this->actingAs($staff)
        ->get(route('students.export'));

    $content = $response->streamedContent();

    expect($content)
        ->toContain('+94771234567')
        ->not->toContain("'+94771234567");
});

// Authentication

it('redirects guests when accessing CSV import and export routes', function (): void {
    $this->actingAsGuest();

    $this->get(route('students.import.create'))
        ->assertRedirect(route('login'));

    $this->get(route('students.import.template'))
        ->assertRedirect(route('login'));

    $this->get(route('students.export'))
        ->assertRedirect(route('login'));

    $this->post(route('students.import.store'), [
        'file' => makeStudentCsvTestFile(
            "student_number,first_name,last_name,enrollment_date\n"
            . "STU-CSV-GUEST,Guest,Student,2026-01-15"
        ),
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('students', [
        'student_number' => 'STU-CSV-GUEST',
    ]);
});