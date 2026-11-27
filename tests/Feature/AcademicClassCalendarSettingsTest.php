<?php

use App\Models\AcademicYear;
use App\Models\User;
use App\Services\ApplicationSettingsService;

beforeEach(function () {
    $this->actingAs(
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ])
    );
});

it('preselects the configured academic year when registered', function () {
    AcademicYear::factory()->create([
        'year' => 2026,
        'name' => 'Academic Year 2026',
    ]);

    app(ApplicationSettingsService::class)->update([
        'current_academic_year' => 2026,
    ]);

    $response = $this->get(route('academic-classes.create'));

    $response->assertOk()
        ->assertSee('Academic Year 2026')
        ->assertSee('value="2026"', false);

    $dom = new DOMDocument;

    libxml_use_internal_errors(true);
    $dom->loadHTML($response->getContent());
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $options = $xpath->query(
        '//select[@name="academic_year"]/option[@value="2026"][@selected]'
    );

    expect($options->length)->toBe(1);
});

it('does not preselect an unregistered configured year', function () {
    AcademicYear::factory()->create([
        'year' => 2027,
        'name' => 'Academic Year 2027',
    ]);

    app(ApplicationSettingsService::class)->update([
        'current_academic_year' => 2026,
    ]);

    $response = $this->get(route('academic-classes.create'));

    $response->assertOk()
        ->assertSee('Academic Year 2027')
        ->assertDontSee('value="2026"', false)
        ->assertDontSee('value="2027" selected', false);
});

it('shows guidance when no academic years are registered', function () {
    $this->get(route('academic-classes.create'))
        ->assertOk()
        ->assertSee('No academic years have been registered.')
        ->assertSee('Register Academic Year');
});
