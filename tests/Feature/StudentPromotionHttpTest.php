<?php

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    $this->student = Student::factory()->create([
        'status' => Student::STATUS_ACTIVE,
    ]);

    $this->sourceClass = AcademicClass::factory()->create([
        'name' => 'Grade 9 - A',
        'grade_level' => 9,
        'academic_year' => 2026,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $this->destinationClass = AcademicClass::factory()->create([
        'name' => 'Grade 10 - A',
        'grade_level' => 10,
        'academic_year' => 2027,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    AcademicYear::factory()->create([
        'year' => 2027,
        'name' => 'Academic Year 2027',
        'start_date' => '2027-01-01',
        'end_date' => '2027-12-31',
    ]);

    $this->sourceEnrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'academic_class_id' => $this->sourceClass->id,
        'enrollment_date' => '2026-01-15',
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    $this->payload = [
        'source_enrollment_id' => $this->sourceEnrollment->id,
        'destination_class_id' => $this->destinationClass->id,
        'promotion_type' => StudentPromotion::TYPE_ADVANCED,
        'promotion_date' => '2027-02-10',
        'notes' => 'Approved academic progression.',
    ];
});

it('allows administrators to view promotion history', function (): void {
    $this->actingAs($this->admin)
        ->get(route('student-promotions.index'))
        ->assertOk()
        ->assertSee('Student Promotions');
});

it('allows staff to view promotion history', function (): void {
    $this->actingAs($this->staff)
        ->get(route('student-promotions.index'))
        ->assertOk()
        ->assertSee('Student Promotions');
});

it('allows administrators to view the promotion form', function (): void {
    $this->actingAs($this->admin)
        ->get(route('student-promotions.create'))
        ->assertOk()
        ->assertSee('Promote Student')
        ->assertSee($this->student->student_number)
        ->assertSee('Grade 10 - A');
});

it('prevents staff from viewing the promotion form', function (): void {
    $this->actingAs($this->staff)
        ->get(route('student-promotions.create'))
        ->assertForbidden();
});

it('allows administrators to promote a student', function (): void {
    $response = $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload);

    $promotion = StudentPromotion::query()->firstOrFail();

    $response->assertRedirect(
        route('student-promotions.show', $promotion)
    )->assertSessionHas('success');

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $this->sourceEnrollment->id,
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $promotion->destination_enrollment_id,
        'student_id' => $this->student->id,
        'academic_class_id' => $this->destinationClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'student.promotion.created',
        'auditable_type' => $promotion->getMorphClass(),
        'auditable_id' => $promotion->id,
    ]);
});

it('prevents staff from performing promotions', function (): void {
    $this->actingAs($this->staff)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertForbidden();

    $this->assertDatabaseCount('student_promotions', 0);
    $this->assertDatabaseCount('student_enrollments', 1);
});

it('rejects missing required promotion fields', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), [])
        ->assertSessionHasErrors([
            'source_enrollment_id',
            'destination_class_id',
            'promotion_type',
            'promotion_date',
        ]);

    $this->assertDatabaseCount('student_promotions', 0);
});

it('rejects an invalid progression type', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), array_merge(
            $this->payload,
            ['promotion_type' => 'invalid']
        ))
        ->assertSessionHasErrors('promotion_type');

    $this->assertDatabaseCount('student_promotions', 0);
});

it('rejects a destination in an unregistered academic year', function (): void {
    $this->destinationClass->update([
        'academic_year' => 2028,
    ]);

    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasErrors('destination_class_id');

    $this->assertDatabaseCount('student_promotions', 0);

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $this->sourceEnrollment->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);
});

it('prevents repeated HTTP promotion requests', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasErrors('source_enrollment_id');

    $this->assertDatabaseCount('student_promotions', 1);
    $this->assertDatabaseCount('student_enrollments', 2);
});

it('displays recorded progression details', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasNoErrors();

    $promotion = StudentPromotion::query()->firstOrFail();

    $this->actingAs($this->staff)
        ->get(route('student-promotions.show', $promotion))
        ->assertOk()
        ->assertSee('Grade 9 - A')
        ->assertSee('Grade 10 - A')
        ->assertSee('Advanced')
        ->assertSee('10 Feb 2027');
});

it('prevents inactive administrators from accessing promotions', function (): void {
    $this->admin->update(['is_active' => false]);

    $this->actingAs($this->admin)
        ->get(route('student-promotions.create'))
        ->assertRedirect();

    $this->assertDatabaseCount('student_promotions', 0);
});

it('prevents changing a promoted source enrollment date', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin)
        ->put(
            route('student-enrollments.update', $this->sourceEnrollment),
            [
                'enrollment_date' => '2026-03-01',
                'status' => StudentEnrollment::STATUS_COMPLETED,
                'notes' => 'Attempted historical date change.',
            ]
        )
        ->assertSessionHasErrors('enrollment_date');

    expect(
        $this->sourceEnrollment->fresh()->enrollment_date->toDateString()
    )->toBe('2026-01-15');
});

it('prevents changing the destination enrollment date after promotion', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasNoErrors();

    $promotion = StudentPromotion::query()->firstOrFail();

    $destinationEnrollment = $promotion->destinationEnrollment;

    $this->actingAs($this->admin)
        ->put(
            route('student-enrollments.update', $destinationEnrollment),
            [
                'enrollment_date' => '2027-03-01',
                'status' => StudentEnrollment::STATUS_ACTIVE,
                'notes' => null,
            ]
        )
        ->assertSessionHasErrors('enrollment_date');

    expect(
        $destinationEnrollment->fresh()->enrollment_date->toDateString()
    )->toBe('2027-02-10');
});

it('prevents changing a promoted source enrollment to withdrawn', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin)
        ->put(
            route('student-enrollments.update', $this->sourceEnrollment),
            [
                'enrollment_date' => '2026-01-15',
                'status' => StudentEnrollment::STATUS_WITHDRAWN,
                'notes' => 'Attempted status change.',
            ]
        )
        ->assertSessionHasErrors('status');

    expect($this->sourceEnrollment->fresh()->status)
        ->toBe(StudentEnrollment::STATUS_COMPLETED);
});

it('allows editing notes on a promoted source enrollment', function (): void {
    $this->actingAs($this->admin)
        ->post(route('student-promotions.store'), $this->payload)
        ->assertSessionHasNoErrors();

    $this->actingAs($this->admin)
        ->put(
            route('student-enrollments.update', $this->sourceEnrollment),
            [
                'enrollment_date' => '2026-01-15',
                'status' => StudentEnrollment::STATUS_COMPLETED,
                'notes' => 'Historical administrative clarification.',
            ]
        )
        ->assertRedirect(
            route('student-enrollments.show', $this->sourceEnrollment)
        )
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $this->sourceEnrollment->id,
        'status' => StudentEnrollment::STATUS_COMPLETED,
        'notes' => 'Historical administrative clarification.',
    ]);
});
