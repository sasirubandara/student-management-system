<?php

namespace App\Services\AcademicCalendar;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicTermService
{
    /**
     * Create a term within an academic year.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(
        array $attributes,
        ?Closure $onWritten = null
    ): AcademicTerm {
        return app(AcademicCalendarTransaction::class)
            ->run(function () use (
                $attributes,
                $onWritten
            ): AcademicTerm {
                $validated = $this->validateAttributes($attributes);

                $academicYear = AcademicYear::query()
                    ->findOrFail($validated['academic_year_id']);

                $this->ensureWithinAcademicYear(
                    $academicYear,
                    $validated
                );

                $this->ensureNoOverlap(
                    $academicYear,
                    $validated
                );

                $academicTerm = AcademicTerm::query()->create($validated);

                if ($onWritten !== null) {
                    $onWritten(
                        $academicTerm,
                        array_keys($validated)
                    );
                }

                return $academicTerm;
            });
    }

    /**
     * Update a term without changing its parent academic year.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        AcademicTerm $academicTerm,
        array $attributes,
        ?Closure $onWritten = null
    ): AcademicTerm {
        return app(AcademicCalendarTransaction::class)
            ->run(function () use (
                $academicTerm,
                $attributes,
                $onWritten
            ): AcademicTerm {
                $term = AcademicTerm::query()
                    ->lockForUpdate()
                    ->findOrFail($academicTerm->id);

                $validated = $this->validateAttributes(
                    $attributes,
                    $term
                );

                $academicYear = AcademicYear::query()
                    ->findOrFail($term->academic_year_id);

                $this->ensureWithinAcademicYear(
                    $academicYear,
                    $validated
                );

                $this->ensureNoOverlap(
                    $academicYear,
                    $validated,
                    $term
                );

                $term->fill($validated);

                $changedFields = array_keys(
                    $term->getDirty()
                );

                if ($changedFields !== []) {
                    $term->save();

                    if ($onWritten !== null) {
                        $onWritten(
                            $term,
                            $changedFields
                        );
                    }
                }

                return $term->refresh();
            });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validateAttributes(
        array $attributes,
        ?AcademicTerm $academicTerm = null
    ): array {
        $academicYearId = $academicTerm?->academic_year_id
            ?? ($attributes['academic_year_id'] ?? null);

        return Validator::make($attributes, [
            'academic_year_id' => $academicTerm === null
                ? ['required', 'integer', 'exists:academic_years,id']
                : ['prohibited'],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'term_number' => [
                'required',
                'integer',
                'between:1,255',
                Rule::unique('academic_terms', 'term_number')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($academicTerm?->id),
            ],

            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after:start_date',
            ],
        ])->validate();
    }

    /**
     * Ensure a term falls entirely within its parent academic year.
     *
     * @param  array<string, mixed>  $validated
     */
    private function ensureWithinAcademicYear(
        AcademicYear $academicYear,
        array $validated
    ): void {
        if (
            $validated['start_date'] < $academicYear->start_date->toDateString()
            || $validated['end_date'] > $academicYear->end_date->toDateString()
        ) {
            throw ValidationException::withMessages([
                'start_date' => 'The term must fall entirely within its academic year.',
            ]);
        }
    }

    /**
     * Prevent overlapping terms within the same academic year.
     *
     * @param  array<string, mixed>  $validated
     */
    private function ensureNoOverlap(
        AcademicYear $academicYear,
        array $validated,
        ?AcademicTerm $academicTerm = null
    ): void {
        $query = $academicYear->terms()
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date']);

        if ($academicTerm !== null) {
            $query->whereKeyNot($academicTerm->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'The term dates overlap an existing term.',
            ]);
        }
    }
}
