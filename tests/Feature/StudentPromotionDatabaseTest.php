<?php

use App\Models\AcademicClass;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use App\Models\User;
use Illuminate\Database\QueryException;

beforeEach(function (): void {
    $this->student = Student::factory()->create();

    $this->sourceEnrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'academic_class_id' => AcademicClass::factory(),
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    $this->destinationEnrollment = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'academic_class_id' => AcademicClass::factory(),
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);
});

it('creates a student promotion with valid relationships', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
    ]);

    $promotion = StudentPromotion::factory()
        ->forEnrollments(
            $this->sourceEnrollment,
            $this->destinationEnrollment
        )
        ->create([
            'promotion_type' => StudentPromotion::TYPE_ADVANCED,
            'promotion_date' => '2027-01-15',
            'promoted_by' => $admin->id,
        ]);

    $this->assertDatabaseHas('student_promotions', [
        'id' => $promotion->id,
        'student_id' => $this->student->id,
        'source_enrollment_id' => $this->sourceEnrollment->id,
        'destination_enrollment_id' => $this->destinationEnrollment->id,
        'promotion_type' => StudentPromotion::TYPE_ADVANCED,
        'promoted_by' => $admin->id,
    ]);

    expect($promotion->student->is($this->student))->toBeTrue()
        ->and($promotion->sourceEnrollment->is($this->sourceEnrollment))->toBeTrue()
        ->and($promotion->destinationEnrollment->is($this->destinationEnrollment))->toBeTrue()
        ->and($promotion->promotedBy->is($admin))->toBeTrue()
        ->and($promotion->promotion_date->toDateString())->toBe('2027-01-15');
});

it('prevents promoting the same source enrollment more than once', function (): void {
    StudentPromotion::factory()
        ->forEnrollments(
            $this->sourceEnrollment,
            $this->destinationEnrollment
        )
        ->create();

    $anotherDestination = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'academic_class_id' => AcademicClass::factory(),
    ]);

    expect(fn () => StudentPromotion::factory()
        ->forEnrollments(
            $this->sourceEnrollment,
            $anotherDestination
        )
        ->create()
    )->toThrow(QueryException::class);
});

it('prevents reusing the same destination enrollment', function (): void {
    StudentPromotion::factory()
        ->forEnrollments(
            $this->sourceEnrollment,
            $this->destinationEnrollment
        )
        ->create();

    $anotherSource = StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'academic_class_id' => AcademicClass::factory(),
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    expect(fn () => StudentPromotion::factory()
        ->forEnrollments(
            $anotherSource,
            $this->destinationEnrollment
        )
        ->create()
    )->toThrow(QueryException::class);
});

it('preserves promotion history when the administrator is deleted', function (): void {
    $admin = User::factory()->create();

    $promotion = StudentPromotion::factory()
        ->forEnrollments(
            $this->sourceEnrollment,
            $this->destinationEnrollment
        )
        ->create([
            'promoted_by' => $admin->id,
        ]);

    $admin->delete();

    $this->assertDatabaseHas('student_promotions', [
        'id' => $promotion->id,
        'student_id' => $this->student->id,
        'promoted_by' => null,
    ]);
});

it('prevents deletion of an enrollment referenced by promotion history', function (): void {
    StudentPromotion::factory()
        ->forEnrollments(
            $this->sourceEnrollment,
            $this->destinationEnrollment
        )
        ->create();

    expect(fn () => $this->sourceEnrollment->delete())
        ->toThrow(QueryException::class);

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $this->sourceEnrollment->id,
    ]);
});

it('allows loading promotion history through existing models', function (): void {
    $promotion = StudentPromotion::factory()
        ->forEnrollments(
            $this->sourceEnrollment,
            $this->destinationEnrollment
        )
        ->create();

    expect($this->student->promotions)
        ->toHaveCount(1);

    expect($this->sourceEnrollment->outgoingPromotion->is($promotion))
        ->toBeTrue();

    expect($this->destinationEnrollment->incomingPromotion->is($promotion))
        ->toBeTrue();
});
