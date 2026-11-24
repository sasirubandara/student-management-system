<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicTermRequest;
use App\Http\Requests\UpdateAcademicTermRequest;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Services\AcademicCalendar\AcademicTermService;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicTermController extends Controller
{
    /**
     * Display the term creation form for an academic year.
     */
    public function create(AcademicYear $academicYear): View
    {
        Gate::authorize('create', AcademicTerm::class);
        Gate::authorize('view', $academicYear);

        return view('academic-terms.create', compact('academicYear'));
    }

    /**
     * Create a term belonging to the selected academic year.
     */
    public function store(
        StoreAcademicTermRequest $request,
        AcademicYear $academicYear,
        AcademicTermService $service,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $validated = $request->validated();

        $validated['academic_year_id'] = $academicYear->id;

        $service->create(
            $validated,
            function (
                AcademicTerm $term,
                array $changedFields
            ) use ($auditLogger): void {
                $auditLogger->record(
                    'academic_term.created',
                    $term,
                    [
                        'academic_year_id' => $term->academic_year_id,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        );

        return to_route('academic-years.show', $academicYear)
            ->with('success', 'Academic term created successfully.');
    }

    /**
     * Display the term editing form.
     */
    public function edit(AcademicTerm $academicTerm): View
    {
        Gate::authorize('update', $academicTerm);

        $academicTerm->load('academicYear');

        return view('academic-terms.edit', compact('academicTerm'));
    }

    /**
     * Update an existing academic term.
     */
    public function update(
        UpdateAcademicTermRequest $request,
        AcademicTerm $academicTerm,
        AcademicTermService $service,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $academicTerm = $service->update(
            $academicTerm,
            $request->validated(),
            function (
                AcademicTerm $term,
                array $changedFields
            ) use ($auditLogger): void {
                $auditLogger->record(
                    'academic_term.updated',
                    $term,
                    [
                        'academic_year_id' => $term->academic_year_id,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        );

        return to_route(
            'academic-years.show',
            $academicTerm->academic_year_id
        )->with('success', 'Academic term updated successfully.');
    }
}
