<?php

namespace App\Http\Requests;

use App\Models\TimetableEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimetableEntryRequest extends FormRequest
{
    /**
     * Determine whether the user can create a timetable entry.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            TimetableEntry::class
        ) ?? false;
    }

    /**
     * Get validation rules for a new timetable entry.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'academic_term_id' => [
                'required',
                'integer',
                Rule::exists('academic_terms', 'id'),
            ],
            'teaching_assignment_id' => [
                'required',
                'integer',
                Rule::exists('teaching_assignments', 'id'),
            ],
            'day_of_week' => [
                'required',
                'integer',
                'between:1,7',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
        ];
    }
}
