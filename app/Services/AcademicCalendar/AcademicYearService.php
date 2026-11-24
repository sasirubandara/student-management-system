<?php

namespace App\Services\AcademicCalendar;

use App\Models\AcademicYear;
use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AcademicYearService
{
    /**
     * Create an academic year after validating its dates.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(
        array $attributes,
        ?Closure $onWritten = null
    ): AcademicYear {
        return app(AcademicCalendarTransaction::class)
            ->run(function () use (
                $attributes,
                $onWritten
            ): AcademicYear {
                $validated = $this->validate($attributes);

                $this->ensureNoOverlap($validated);

                $academicYear = AcademicYear::query()->create($validated);

                if ($onWritten !== null) {
                    $onWritten(
                        $academicYear,
                        array_keys($validated)
                    );
                }

                return $academicYear;
            });
    }

    /**
     * Update an academic year without invalidating existing terms.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        AcademicYear $academicYear,
        array $attributes,
        ?Closure $onWritten = null
    ): AcademicYear {
        return app(AcademicCalendarTransaction::class)
            ->run(function () use (
                $academicYear,
                $attributes,
                $onWritten
            ): AcademicYear {
                $academicYear = AcademicYear::query()
                    ->lockForUpdate()
                    ->findOrFail($academicYear->id);

                $validated = $this->validate(
                    $attributes,
                    $academicYear
                );

                $this->ensureNoOverlap(
                    $validated,
                    $academicYear
                );

                $this->ensureTermsRemainWithinYear(
                    $academicYear,
                    $validated
                );

                $academicYear->fill($validated);

                $changedFields = array_keys(
                    $academicYear->getDirty()
                );

                if ($changedFields !== []) {
                    $academicYear->save();

                    if ($onWritten !== null) {
                        $onWritten(
                            $academicYear,
                            $changedFields
                        );
                    }
                }

                return $academicYear->refresh();
            });
    }

    /**
     * Validate academic year attributes.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function validate(
        array $attributes,
        ?AcademicYear $academicYear = null
    ): array {
        return Validator::make($attributes, [
            'year' => [
                'required',
                'integer',
                'between:2000,2100',
                Rule::unique('academic_years', 'year')
                    ->ignore($academicYear?->id),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
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
     * Reject overlapping academic year periods.
     *
     * @param  array<string, mixed>  $validated
     */
    private function ensureNoOverlap(
        array $validated,
        ?AcademicYear $academicYear = null
    ): void {
        $query = AcademicYear::query()
            ->whereDate('start_date', '<=', $validated['end_date'])
            ->whereDate('end_date', '>=', $validated['start_date']);

        if ($academicYear !== null) {
            $query->whereKeyNot($academicYear->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => 'The academic year dates overlap an existing academic year.',
            ]);
        }
    }

    /**
     * Reject changes that would leave existing terms outside the year.
     *
     * @param  array<string, mixed>  $validated
     */
    private function ensureTermsRemainWithinYear(
        AcademicYear $academicYear,
        array $validated
    ): void {
        $hasInvalidTerms = $academicYear->terms()
            ->where(function ($query) use ($validated): void {
                $query->whereDate(
                    'start_date',
                    '<',
                    $validated['start_date']
                )->orWhereDate(
                    'end_date',
                    '>',
                    $validated['end_date']
                );
            })
            ->exists();

        if ($hasInvalidTerms) {
            throw ValidationException::withMessages([
                'start_date' => 'The new dates must include all existing academic terms.',
            ]);
        }
    }
}
