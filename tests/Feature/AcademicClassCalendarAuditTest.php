<?php

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ])
    );
});

it('audits a change to the academic year of a class', function () {
    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2025,
    ]);

    AcademicYear::factory()->create([
        'year' => 2026,
        'name' => 'Academic Year 2026',
    ]);

    $response = $this->put(
        route('academic-classes.update', $academicClass),
        [
            'name' => $academicClass->name,
            'code' => $academicClass->code,
            'grade_level' => $academicClass->grade_level,
            'academic_year' => 2026,
            'description' => $academicClass->description,
            'status' => $academicClass->status,
        ]
    );

    $response->assertSessionHasNoErrors();

    $this->assertDatabaseHas('audit_logs', [
        'event' => 'academic_class.updated',
        'auditable_type' => AcademicClass::class,
        'auditable_id' => $academicClass->id,
    ]);
});

it('does not audit an unchanged academic class update', function () {
    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2025,
    ]);

    $payload = [
        'name' => $academicClass->name,
        'code' => $academicClass->code,
        'grade_level' => $academicClass->grade_level,
        'academic_year' => $academicClass->academic_year,
        'description' => $academicClass->description,
        'status' => $academicClass->status,
    ];

    $this->put(
        route('academic-classes.update', $academicClass),
        $payload
    )->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('audit_logs', [
        'event' => 'academic_class.updated',
        'auditable_type' => AcademicClass::class,
        'auditable_id' => $academicClass->id,
    ]);
});
