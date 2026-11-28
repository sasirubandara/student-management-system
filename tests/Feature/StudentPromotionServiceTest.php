<?php

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\AttendanceSession;
use App\Models\AuditLog;
use App\Models\Examination;
use App\Models\ExaminationResult;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentPromotions\StudentPromotionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->student = Student::factory()->create([
        'status' => Student::STATUS_ACTIVE,
    ]);

    $this->sourceClass = AcademicClass::factory()->create([
        'grade_level' => 9,
        'academic_year' => 2026,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $this->destinationClass = AcademicClass::factory()->create([
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

    $this->promotionService = app(StudentPromotionService::class);
});

it('promotes a student and preserves the original enrollment', function (): void {
    $promotion = $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        'Promoted to Grade 10.',
        $this->admin
    );

    expect($promotion)->toBeInstanceOf(StudentPromotion::class);

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $this->sourceEnrollment->id,
        'student_id' => $this->student->id,
        'academic_class_id' => $this->sourceClass->id,
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $promotion->destination_enrollment_id,
        'student_id' => $this->student->id,
        'academic_class_id' => $this->destinationClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    expect($promotion->source_enrollment_id)
        ->toBe($this->sourceEnrollment->id)
        ->and($promotion->promotion_type)
        ->toBe(StudentPromotion::TYPE_ADVANCED);

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'student.promotion.created',
        'auditable_type' => $promotion->getMorphClass(),
        'auditable_id' => $promotion->id,
        'user_id' => $this->admin->id,
    ]);

    $audit = AuditLog::query()
        ->where('event', 'student.promotion.created')
        ->where('auditable_id', $promotion->id)
        ->firstOrFail();

    expect($audit->properties['source_enrollment_id'])
        ->toBe($this->sourceEnrollment->id);

    expect(json_encode($audit->properties))
        ->not->toContain('Promoted to Grade 10.');
});

it('retains the original enrollment identity after promotion', function (): void {
    $originalEnrollmentId = $this->sourceEnrollment->id;
    $originalClassId = $this->sourceEnrollment->academic_class_id;
    $originalEnrollmentDate = $this->sourceEnrollment
        ->enrollment_date
        ->toDateString();

    $promotion = $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    );

    $original = StudentEnrollment::findOrFail(
        $originalEnrollmentId
    );

    expect($original->id)->toBe($originalEnrollmentId)
        ->and($original->academic_class_id)->toBe($originalClassId)
        ->and($original->enrollment_date->toDateString())
        ->toBe($originalEnrollmentDate)
        ->and($original->status)
        ->toBe(StudentEnrollment::STATUS_COMPLETED);

    expect($promotion->source_enrollment_id)
        ->toBe($originalEnrollmentId);

    expect($promotion->destination_enrollment_id)
        ->not->toBe($originalEnrollmentId);

    $this->assertDatabaseCount('student_enrollments', 2);
});

it('rejects promotion attempts by staff users', function (): void {
    $staff = User::factory()->create([
        'role' => User::ROLE_STAFF,
        'is_active' => true,
    ]);

    expect(fn () => $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $staff
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('student_promotions', 0);
});

it('rejects inactive administrators', function (): void {
    $this->admin->update(['is_active' => false]);

    expect(fn () => $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('student_promotions', 0);
});

it('rejects an invalid destination grade', function (): void {
    $this->destinationClass->update([
        'grade_level' => 11,
    ]);

    expect(fn () => $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    ))->toThrow(ValidationException::class);

    $this->sourceEnrollment->refresh();

    expect($this->sourceEnrollment->status)
        ->toBe(StudentEnrollment::STATUS_ACTIVE);

    $this->assertDatabaseCount('student_promotions', 0);
});

it('rejects a destination in an earlier academic year', function (): void {
    $this->destinationClass->update([
        'academic_year' => 2025,
    ]);

    expect(fn () => $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('student_promotions', 0);
});

it('rejects a promotion date outside the destination year', function (): void {
    expect(fn () => $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2028-01-15',
        null,
        $this->admin
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('student_promotions', 0);
});

it('rejects repeated promotion of the same enrollment', function (): void {
    $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    );

    expect(fn () => $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('student_promotions', 1);
    $this->assertDatabaseCount('student_enrollments', 2);
});

it('rejects a student with conflicting active enrollments', function (): void {
    StudentEnrollment::factory()->create([
        'student_id' => $this->student->id,
        'academic_class_id' => AcademicClass::factory(),
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    expect(fn () => $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    ))->toThrow(ValidationException::class);

    $this->assertDatabaseCount('student_promotions', 0);
});

it('supports repeating a grade in a later academic year', function (): void {
    $this->destinationClass->update([
        'grade_level' => 9,
    ]);

    $promotion = $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_REPEATED,
        '2027-02-10',
        'Grade repetition approved.',
        $this->admin
    );

    expect($promotion->promotion_type)
        ->toBe(StudentPromotion::TYPE_REPEATED);

    $this->assertDatabaseHas('student_enrollments', [
        'id' => $promotion->destination_enrollment_id,
        'academic_class_id' => $this->destinationClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);
});

it('rolls back the entire promotion when audit logging fails', function (): void {
    $auditLogger = Mockery::mock(AuditLogger::class);

    $auditLogger->shouldReceive('record')
        ->once()
        ->andThrow(new RuntimeException('Simulated audit failure.'));

    $service = new StudentPromotionService($auditLogger);

    expect(fn () => $service->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    ))->toThrow(RuntimeException::class);

    $this->sourceEnrollment->refresh();

    expect($this->sourceEnrollment->status)
        ->toBe(StudentEnrollment::STATUS_ACTIVE);

    $this->assertDatabaseCount('student_enrollments', 1);
    $this->assertDatabaseCount('student_promotions', 0);

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'student.promotion.created',
    ]);
});

it('preserves attendance and examination history after student promotion', function (): void {
    // Create a teaching assignment for the student's original class.
    $teachingAssignment = TeachingAssignment::factory()->create([
        'academic_class_id' => $this->sourceClass->id,
    ]);

    // Create a historical attendance session.
    $attendanceSession = AttendanceSession::factory()
        ->forTeachingAssignment($teachingAssignment)
        ->create([
            'attendance_date' => '2026-06-15',
        ]);

    // Record attendance against the ORIGINAL enrollment.
    $attendance = StudentAttendance::factory()
        ->forSession($attendanceSession)
        ->forEnrollment($this->sourceEnrollment)
        ->create([
            'status' => StudentAttendance::STATUS_PRESENT,
            'remarks' => 'Present during the lesson.',
        ]);

    // Create an examination for the original class.
    $examination = Examination::factory()
        ->forTeachingAssignment($teachingAssignment)
        ->create([
            'title' => 'Grade 9 Midterm Examination',
            'exam_date' => '2026-07-10',
            'maximum_marks' => '100.00',
            'pass_marks' => '40.00',
        ]);

    // Record the examination result against the ORIGINAL enrollment.
    $result = ExaminationResult::factory()
        ->forExamination($examination)
        ->forEnrollment($this->sourceEnrollment)
        ->create([
            'marks_obtained' => '85.00',
        ]);

    $originalEnrollmentId = $this->sourceEnrollment->id;

    // Perform the promotion through the real service.
    $promotion = $this->promotionService->promote(
        $this->sourceEnrollment,
        $this->destinationClass,
        StudentPromotion::TYPE_ADVANCED,
        '2027-02-10',
        null,
        $this->admin
    );

    // The original enrollment remains in the database.
    $this->assertDatabaseHas('student_enrollments', [
        'id' => $originalEnrollmentId,
        'student_id' => $this->student->id,
        'academic_class_id' => $this->sourceClass->id,
        'status' => StudentEnrollment::STATUS_COMPLETED,
    ]);

    // Historical attendance still references the original enrollment.
    $this->assertDatabaseHas('student_attendances', [
        'id' => $attendance->id,
        'attendance_session_id' => $attendanceSession->id,
        'student_enrollment_id' => $originalEnrollmentId,
        'status' => StudentAttendance::STATUS_PRESENT,
    ]);

    // Historical examination results remain unchanged.
    $this->assertDatabaseHas('examination_results', [
        'id' => $result->id,
        'examination_id' => $examination->id,
        'student_enrollment_id' => $originalEnrollmentId,
        'marks_obtained' => '85.00',
        'grade' => $result->grade,
    ]);

    // The destination is a separate active enrollment.
    $this->assertDatabaseHas('student_enrollments', [
        'id' => $promotion->destination_enrollment_id,
        'student_id' => $this->student->id,
        'academic_class_id' => $this->destinationClass->id,
        'status' => StudentEnrollment::STATUS_ACTIVE,
    ]);

    // Old academic results must not be reassigned to the new enrollment.
    $this->assertDatabaseMissing('student_attendances', [
        'id' => $attendance->id,
        'student_enrollment_id' => $promotion->destination_enrollment_id,
    ]);

    $this->assertDatabaseMissing('examination_results', [
        'id' => $result->id,
        'student_enrollment_id' => $promotion->destination_enrollment_id,
    ]);

    // Check the relationships as well as the database values.
    expect($attendance->fresh()->studentEnrollment->id)
        ->toBe($originalEnrollmentId);

    expect($result->fresh()->studentEnrollment->id)
        ->toBe($originalEnrollmentId);

    // One original enrollment and one destination enrollment.
    $this->assertDatabaseCount('student_enrollments', 2);
    $this->assertDatabaseCount('student_attendances', 1);
    $this->assertDatabaseCount('examination_results', 1);
    $this->assertDatabaseCount('student_promotions', 1);
});
