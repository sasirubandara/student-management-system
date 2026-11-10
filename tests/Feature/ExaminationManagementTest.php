<?php

use App\Models\AcademicClass;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Examination Test Helpers
|--------------------------------------------------------------------------
*/

function makeExaminationManagementContext(): array
{
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
    ]);

    $teacher = Teacher::factory()->create([
        'status' => Teacher::STATUS_ACTIVE,
    ]);

    $academicClass = AcademicClass::factory()->create([
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $subject = Subject::factory()->create([
        'status' => Subject::STATUS_ACTIVE,
    ]);

    $teachingAssignment = TeachingAssignment::factory()->create([
        'teacher_id' => $teacher->id,
        'academic_class_id' => $academicClass->id,
        'subject_id' => $subject->id,
    ]);

    $student = Student::factory()->create([
        'status' => Student::STATUS_ACTIVE,
    ]);

    $enrollment = StudentEnrollment::factory()->create([
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    return compact(
        'admin',
        'staff',
        'teacher',
        'academicClass',
        'subject',
        'teachingAssignment',
        'student',
        'enrollment'
    );
}

function makeExaminationManagementExamination(
    array $context,
    array $attributes = []
): Examination {
    return Examination::factory()->create(array_merge([
        'teaching_assignment_id' => $context['teachingAssignment']->id,
        'created_by' => $context['admin']->id,
        'maximum_marks' => '100.00',
        'pass_marks' => '40.00',
    ], $attributes));
}

function makeExaminationManagementEnrollment(
    AcademicClass $academicClass,
    array $studentAttributes = [],
    array $enrollmentAttributes = []
): array {
    $student = Student::factory()->create(array_merge([
        'status' => Student::STATUS_ACTIVE,
    ], $studentAttributes));

    $enrollment = StudentEnrollment::factory()->create(array_merge([
        'student_id' => $student->id,
        'academic_class_id' => $academicClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ], $enrollmentAttributes));

    return compact('student', 'enrollment');
}

function makeExaminationManagementResult(
    Examination $examination,
    StudentEnrollment $enrollment,
    array $attributes = []
): ExaminationResult {
    return ExaminationResult::factory()
        ->forExamination($examination)
        ->forEnrollment($enrollment)
        ->create(array_merge([
            'marks_obtained' => '75.00',
            'remarks' => null,
        ], $attributes));
}

/*
|--------------------------------------------------------------------------
| Examination Listing and Viewing
|--------------------------------------------------------------------------
*/

it('allows administrators to view examinations', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.index'));

    $response->assertOk()
        ->assertSee($examination->title)
        ->assertSee($context['teacher']->teacher_number)
        ->assertSee($context['academicClass']->name)
        ->assertSee($context['subject']->name);
});

it('allows staff users to view examinations', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['staff'])
        ->get(route('examinations.index'));

    $response->assertOk()
        ->assertSee($examination->title);
});

it('displays the examination navigation link to authorized users', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['staff'])
        ->get(route('examinations.index'));

    $response->assertOk()
        ->assertSee(route('examinations.index'), false);
});

it('allows administrators and staff to view examination details', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['staff'])
        ->get(route('examinations.show', $examination));

    $response->assertOk()
        ->assertSee($examination->title)
        ->assertSee($context['teacher']->teacher_number)
        ->assertSee($context['academicClass']->name)
        ->assertSee($context['subject']->name);
});

it('searches examinations by title', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context, [
        'title' => 'UNIQUE EXAMINATION SEARCH',
    ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.index', [
            'search' => 'UNIQUE EXAMINATION SEARCH',
        ]));

    $response->assertOk()
        ->assertSee($examination->title);
});

it('searches examinations by teacher number', function (): void {
    $context = makeExaminationManagementContext();

    $context['teacher']->update([
        'teacher_number' => 'TCH-EXAM-SEARCH-001',
    ]);

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.index', [
            'search' => 'TCH-EXAM-SEARCH-001',
        ]));

    $response->assertOk()
        ->assertSee($examination->title);
});

it('searches examinations by academic class code', function (): void {
    $context = makeExaminationManagementContext();

    $context['academicClass']
        ->forceFill([
            'code' => 'EXAM-CLASS-SEARCH',
        ])
        ->save();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.index', [
            'search' => 'EXAM-CLASS-SEARCH',
        ]));

    $response->assertOk()
        ->assertSee($examination->title);
});

it('searches examinations by subject code', function (): void {
    $context = makeExaminationManagementContext();

    $context['subject']
        ->forceFill([
            'code' => 'EXAM-SUBJECT-SEARCH',
        ])
        ->save();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.index', [
            'search' => 'EXAM-SUBJECT-SEARCH',
        ]));

    $response->assertOk()
        ->assertSee($examination->title);
});

it('paginates examinations with ten records per page', function (): void {
    $context = makeExaminationManagementContext();

    Examination::factory()
        ->count(12)
        ->create([
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'created_by' => $context['admin']->id,
        ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.index'));

    $response->assertOk()
        ->assertViewHas('examinations', function ($examinations) {
            return $examinations->perPage() === 10
                && $examinations->total() === 12;
        });
});

/*
|--------------------------------------------------------------------------
| Examination Creation
|--------------------------------------------------------------------------
*/

it('allows administrators to view the examination creation form', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.create'));

    $response->assertOk()
        ->assertSee($context['teacher']->teacher_number)
        ->assertSee($context['subject']->name);
});

it('allows staff users to view the examination creation form', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['staff'])
        ->get(route('examinations.create'));

    $response->assertOk();
});

it('allows administrators to create examinations', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'First Term Examination',
            'exam_type' => Examination::TYPE_TERM_EXAMINATION,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 100,
            'pass_marks' => 40,
        ]);

    $examination = Examination::query()
        ->where('title', 'First Term Examination')
        ->first();

    $response->assertRedirect(
        route('examinations.show', $examination)
    );

    expect($examination)->not->toBeNull()
        ->and($examination->created_by)->toBe($context['admin']->id);
});

it('allows staff users to create examinations', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['staff'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Staff Created Examination',
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 50,
            'pass_marks' => 20,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('examinations', [
        'title' => 'Staff Created Examination',
        'created_by' => $context['staff']->id,
    ]);
});

it('rejects duplicate examinations for the same assignment title and date', function (): void {
    $context = makeExaminationManagementContext();

    makeExaminationManagementExamination($context, [
        'title' => 'Duplicate Examination',
        'exam_date' => '2026-11-10',
    ]);

    $response = $this->actingAs($context['admin'])
        ->from(route('examinations.create'))
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Duplicate Examination',
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => '2026-11-10',
            'maximum_marks' => 100,
            'pass_marks' => 40,
        ]);

    $response->assertSessionHasErrors('title');

    expect(
        Examination::where('title', 'Duplicate Examination')->count()
    )->toBe(1);
});

it('requires mandatory examination fields', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), []);

    $response->assertSessionHasErrors([
        'teaching_assignment_id',
        'title',
        'exam_type',
        'exam_date',
        'maximum_marks',
    ]);
});

it('rejects invalid examination types', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Invalid Type Examination',
            'exam_type' => 'invalid_type',
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 100,
        ]);

    $response->assertSessionHasErrors('exam_type');
});

it('rejects zero maximum marks', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Invalid Marks Examination',
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 0,
        ]);

    $response->assertSessionHasErrors('maximum_marks');
});

it('rejects pass marks greater than maximum marks', function (): void {
    $context = makeExaminationManagementContext();

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Invalid Pass Marks',
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 50,
            'pass_marks' => 60,
        ]);

    $response->assertSessionHasErrors('pass_marks');
});

it('rejects examinations for inactive teachers', function (): void {
    $context = makeExaminationManagementContext();

    $context['teacher']->update([
        'status' => Teacher::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Inactive Teacher Examination',
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 100,
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');
});

it('rejects examinations for inactive academic classes', function (): void {
    $context = makeExaminationManagementContext();

    $context['academicClass']->update([
        'status' => AcademicClass::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Inactive Class Examination',
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 100,
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');
});

it('rejects examinations for inactive subjects', function (): void {
    $context = makeExaminationManagementContext();

    $context['subject']->update([
        'status' => Subject::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->post(route('examinations.store'), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Inactive Subject Examination',
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 100,
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');
});

/*
|--------------------------------------------------------------------------
| Examination Updates
|--------------------------------------------------------------------------
*/

it('allows administrators and staff to edit examinations', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['staff'])
        ->get(route('examinations.edit', $examination));

    $response->assertOk()
        ->assertSee($examination->title);
});

it('allows administrators to update examination information', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.update', $examination), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => 'Updated Examination Title',
            'exam_type' => Examination::TYPE_MIDTERM,
            'exam_date' => now()->toDateString(),
            'maximum_marks' => 100,
            'pass_marks' => 40,
        ]);

    $response->assertRedirect(
        route('examinations.show', $examination)
    );

    $this->assertDatabaseHas('examinations', [
        'id' => $examination->id,
        'title' => 'Updated Examination Title',
    ]);
});

it('prevents duplicate examination details during updates', function (): void {
    $context = makeExaminationManagementContext();

    $first = makeExaminationManagementExamination($context, [
        'title' => 'Existing Examination',
    ]);

    $second = makeExaminationManagementExamination($context, [
        'title' => 'Second Examination',
    ]);

    $response = $this->actingAs($context['admin'])
        ->from(route('examinations.edit', $second))
        ->put(route('examinations.update', $second), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => $first->title,
            'exam_type' => Examination::TYPE_QUIZ,
            'exam_date' => $first->exam_date->toDateString(),
            'maximum_marks' => 100,
            'pass_marks' => 40,
        ]);

    $response->assertSessionHasErrors('title');
});

it('prevents changing the teaching assignment after results exist', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    $otherAssignment = TeachingAssignment::factory()->create();

    $response = $this->actingAs($context['admin'])
        ->from(route('examinations.edit', $examination))
        ->put(route('examinations.update', $examination), [
            'teaching_assignment_id' => $otherAssignment->id,
            'title' => $examination->title,
            'exam_type' => $examination->exam_type,
            'exam_date' => $examination->exam_date->toDateString(),
            'maximum_marks' => 100,
            'pass_marks' => 40,
        ]);

    $response->assertSessionHasErrors('teaching_assignment_id');
});

it('prevents reducing maximum marks below existing results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    makeExaminationManagementResult(
        $examination,
        $context['enrollment'],
        ['marks_obtained' => '80.00']
    );

    $response = $this->actingAs($context['admin'])
        ->from(route('examinations.edit', $examination))
        ->put(route('examinations.update', $examination), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => $examination->title,
            'exam_type' => $examination->exam_type,
            'exam_date' => $examination->exam_date->toDateString(),
            'maximum_marks' => 70,
            'pass_marks' => 40,
        ]);

    $response->assertSessionHasErrors('maximum_marks');
});

it('recalculates existing grades when maximum marks change', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $result = makeExaminationManagementResult(
        $examination,
        $context['enrollment'],
        ['marks_obtained' => '80.00']
    );

    expect($result->fresh()->grade)->toBe('B');

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.update', $examination), [
            'teaching_assignment_id' => $context['teachingAssignment']->id,
            'title' => $examination->title,
            'exam_type' => $examination->exam_type,
            'exam_date' => $examination->exam_date->toDateString(),
            'maximum_marks' => 90,
            'pass_marks' => 40,
        ]);

    $response->assertRedirect();

    expect($result->fresh()->grade)->toBe('A');
});

/*
|--------------------------------------------------------------------------
| Examination Deletion
|--------------------------------------------------------------------------
*/

it('allows administrators to delete examinations without results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->delete(route('examinations.destroy', $examination));

    $response->assertRedirect(route('examinations.index'));

    $this->assertDatabaseMissing('examinations', [
        'id' => $examination->id,
    ]);
});

it('prevents staff users from deleting examinations', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $this->actingAs($context['staff'])
        ->delete(route('examinations.destroy', $examination))
        ->assertForbidden();

    $this->assertDatabaseHas('examinations', [
        'id' => $examination->id,
    ]);
});

it('prevents deleting examinations that contain results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    $response = $this->actingAs($context['admin'])
        ->delete(route('examinations.destroy', $examination));

    $response->assertRedirect(
        route('examinations.show', $examination)
    );

    $this->assertDatabaseHas('examinations', [
        'id' => $examination->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Examination Result Recording
|--------------------------------------------------------------------------
*/

it('allows administrators to record results for multiple students', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $second = makeExaminationManagementEnrollment(
        $context['academicClass']
    );

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 90,
                ],
                [
                    'student_enrollment_id' => $second['enrollment']->id,
                    'marks_obtained' => 65,
                ],
            ],
        ]);

    $response->assertRedirect();

    expect($examination->results()->count())->toBe(2);

    $this->assertDatabaseHas('examination_results', [
        'examination_id' => $examination->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'marks_obtained' => '90.00',
        'grade' => 'A',
    ]);

    $this->assertDatabaseHas('examination_results', [
        'examination_id' => $examination->id,
        'student_enrollment_id' => $second['enrollment']->id,
        'marks_obtained' => '65.00',
        'grade' => 'C',
    ]);
});

it('allows staff users to record examination results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['staff'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 75,
                ],
            ],
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('examination_results', [
        'examination_id' => $examination->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'grade' => 'B',
    ]);
});

it('updates existing examination results instead of creating duplicates', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 95,
                ],
            ],
        ]);

    $response->assertRedirect();

    expect($examination->results()->count())->toBe(1);

    $this->assertDatabaseHas('examination_results', [
        'examination_id' => $examination->id,
        'student_enrollment_id' => $context['enrollment']->id,
        'marks_obtained' => '95.00',
        'grade' => 'A',
    ]);
});

it('calculates grades using the configured grading scale', function (
    float $marks,
    string $expectedGrade
): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => $marks,
                ],
            ],
        ])
        ->assertRedirect();

    expect(
        $examination->results()->first()->grade
    )->toBe($expectedGrade);
})->with([
    'grade A' => [90, 'A'],
    'grade B' => [75, 'B'],
    'grade C' => [60, 'C'],
    'grade D' => [45, 'D'],
    'grade F' => [20, 'F'],
]);

it('rejects negative examination marks', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => -5,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results.0.marks_obtained');
});

it('rejects marks greater than maximum marks', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 101,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results.0.marks_obtained');
});

it('requires at least one examination result', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [],
        ]);

    $response->assertSessionHasErrors('results');
});

it('rejects duplicate student enrollments in a single result submission', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 80,
                ],
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 90,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results.1.student_enrollment_id');
});

/*
|--------------------------------------------------------------------------
| Enrollment Eligibility
|--------------------------------------------------------------------------
*/

it('rejects enrollments from a different academic class', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $otherClass = AcademicClass::factory()->create([
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $other = makeExaminationManagementEnrollment($otherClass);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $other['enrollment']->id,
                    'marks_obtained' => 80,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results');
});

it('rejects completed enrollments when recording new results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $context['enrollment']->update([
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 80,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results');
});

it('rejects withdrawn enrollments when recording new results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $context['enrollment']->update([
        'status' => StudentEnrollment::STATUS_WITHDRAWN,
    ]);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 80,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results');
});

it('rejects inactive students when recording new results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $context['student']->update([
        'status' => Student::STATUS_INACTIVE,
    ]);

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 80,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results');
});

it('rejects archived students when recording new results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $context['student']->delete();

    $response = $this->actingAs($context['admin'])
        ->put(route('examinations.results.save', $examination), [
            'results' => [
                [
                    'student_enrollment_id' => $context['enrollment']->id,
                    'marks_obtained' => 80,
                ],
            ],
        ]);

    $response->assertSessionHasErrors('results');
});

/*
|--------------------------------------------------------------------------
| Historical Examination Results
|--------------------------------------------------------------------------
*/

it('preserves and displays results for archived students', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    $context['student']->delete();

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.show', $examination));

    $response->assertOk()
        ->assertSee($context['student']->student_number)
        ->assertSee($context['student']->first_name)
        ->assertSee($context['student']->last_name);
});

it('preserves results for completed enrollments', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    $context['enrollment']->update([
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    $response = $this->actingAs($context['admin'])
        ->get(route('examinations.show', $examination));

    $response->assertOk()
        ->assertSee($context['student']->student_number);
});

/*
|--------------------------------------------------------------------------
| Examination Result Deletion
|--------------------------------------------------------------------------
*/

it('allows administrators to remove individual results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $result = makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    $response = $this->actingAs($context['admin'])
        ->delete(route('examination-results.destroy', $result));

    $response->assertRedirect(
        route('examinations.show', $examination)
    );

    $this->assertDatabaseMissing('examination_results', [
        'id' => $result->id,
    ]);
});

it('prevents staff users from removing individual results', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    $result = makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    $this->actingAs($context['staff'])
        ->delete(route('examination-results.destroy', $result))
        ->assertForbidden();

    $this->assertDatabaseHas('examination_results', [
        'id' => $result->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Database Relationship Protection
|--------------------------------------------------------------------------
*/

it('prevents deletion of an examination with related results at database level', function (): void {
    $context = makeExaminationManagementContext();

    $examination = makeExaminationManagementExamination($context);

    makeExaminationManagementResult(
        $examination,
        $context['enrollment']
    );

    expect(fn () => DB::table('examinations')
        ->where('id', $examination->id)
        ->delete()
    )->toThrow(\Illuminate\Database\QueryException::class);
});