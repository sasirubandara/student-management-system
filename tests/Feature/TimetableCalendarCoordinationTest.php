<?php

use App\Models\AcademicClass;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a class for a registered academic year', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    AcademicYear::factory()->create([
        'year' => 2046,
        'name' => 'Academic Year 2046',
    ]);

    $this->actingAs($admin)
        ->post(route('academic-classes.store'), [
            'name' => 'Grade 8 - Timetable Coordination',
            'code' => 'TIM-2046-8',
            'grade_level' => 8,
            'academic_year' => 2046,
            'description' => null,
            'status' => AcademicClass::STATUS_ACTIVE,
        ])
        ->assertRedirect(route('academic-classes.index'));

    $this->assertDatabaseHas('academic_classes', [
        'code' => 'TIM-2046-8',
        'academic_year' => 2046,
    ]);
});

it('rejects creating a class for an unregistered year', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('academic-classes.store'), [
            'name' => 'Invalid Calendar Class',
            'code' => 'TIM-INVALID-8',
            'grade_level' => 8,
            'academic_year' => 2047,
            'description' => null,
            'status' => AcademicClass::STATUS_ACTIVE,
        ])
        ->assertSessionHasErrors('academic_year');

    $this->assertDatabaseMissing('academic_classes', [
        'code' => 'TIM-INVALID-8',
    ]);
});

it('preserves historical classes during ordinary updates', function (): void {
    $admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'is_active' => true,
    ]);

    $class = AcademicClass::factory()->create([
        'academic_year' => 2048,
        'status' => AcademicClass::STATUS_ACTIVE,
    ]);

    $this->actingAs($admin)
        ->put(route('academic-classes.update', $class), [
            'name' => 'Updated Historical Class',
            'code' => $class->code,
            'grade_level' => $class->grade_level,
            'academic_year' => 2048,
            'description' => $class->description,
            'status' => $class->status,
        ])
        ->assertRedirect(route('academic-classes.show', $class));

    expect($class->fresh()->name)
        ->toBe('Updated Historical Class');
});
