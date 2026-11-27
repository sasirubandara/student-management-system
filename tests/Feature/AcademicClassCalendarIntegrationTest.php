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

function academicClassCalendarPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Grade 10 - Calendar Integration',
        'code' => 'CAL-INT-10',
        'grade_level' => 10,
        'academic_year' => 2026,
        'description' => 'Academic calendar integration test.',
        'status' => AcademicClass::STATUS_ACTIVE,
    ], $overrides);
}

it('allows creating a class for a registered academic year', function () {
    AcademicYear::factory()->create([
        'year' => 2026,
        'name' => 'Academic Year 2026',
    ]);

    $response = $this->post(
        route('academic-classes.store'),
        academicClassCalendarPayload()
    );

    $response->assertRedirect(route('academic-classes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('academic_classes', [
        'code' => 'CAL-INT-10',
        'academic_year' => 2026,
    ]);
});

it('rejects creating a class for an unregistered academic year', function () {
    $response = $this->post(
        route('academic-classes.store'),
        academicClassCalendarPayload([
            'academic_year' => 2028,
        ])
    );

    $response->assertSessionHasErrors('academic_year');

    $this->assertDatabaseMissing('academic_classes', [
        'code' => 'CAL-INT-10',
    ]);
});

it('allows changing a class to a registered academic year', function () {
    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2024,
    ]);

    AcademicYear::factory()->create([
        'year' => 2027,
        'name' => 'Academic Year 2027',
    ]);

    $response = $this->put(
        route('academic-classes.update', $academicClass),
        academicClassCalendarPayload([
            'code' => $academicClass->code,
            'academic_year' => 2027,
        ])
    );

    $response->assertRedirect(
        route('academic-classes.show', $academicClass)
    )->assertSessionHas('success');

    $this->assertDatabaseHas('academic_classes', [
        'id' => $academicClass->id,
        'academic_year' => 2027,
    ]);
});

it('rejects changing a class to an unregistered academic year', function () {
    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2024,
    ]);

    $response = $this->put(
        route('academic-classes.update', $academicClass),
        academicClassCalendarPayload([
            'code' => $academicClass->code,
            'academic_year' => 2028,
        ])
    );

    $response->assertSessionHasErrors('academic_year');

    $this->assertDatabaseHas('academic_classes', [
        'id' => $academicClass->id,
        'academic_year' => 2024,
    ]);
});

it('allows editing a historical class without changing its year', function () {
    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2023,
    ]);

    $response = $this->put(
        route('academic-classes.update', $academicClass),
        academicClassCalendarPayload([
            'name' => 'Updated Historical Class',
            'code' => $academicClass->code,
            'academic_year' => 2023,
        ])
    );

    $response->assertRedirect(
        route('academic-classes.show', $academicClass)
    )->assertSessionHas('success');

    $this->assertDatabaseHas('academic_classes', [
        'id' => $academicClass->id,
        'name' => 'Updated Historical Class',
        'academic_year' => 2023,
    ]);
});

it('displays registered academic years in the class creation form', function () {
    AcademicYear::factory()->create([
        'year' => 2026,
        'name' => 'Academic Year 2026',
    ]);

    $this->get(route('academic-classes.create'))
        ->assertOk()
        ->assertSee('Academic Year 2026')
        ->assertSee('value="2026"', false);
});

it('preserves historical academic years in the class editing form', function () {
    $academicClass = AcademicClass::factory()->create([
        'academic_year' => 2023,
    ]);

    $this->get(route('academic-classes.edit', $academicClass))
        ->assertOk()
        ->assertSee('Historical')
        ->assertSee('value="2023"', false);
});
