<?php

namespace App\Http\Requests;

use App\Models\TimetableEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimetableEntryRequest extends FormRequest
{
    /**
     * Determine whether the user can update this timetable entry.
     */
    public function authorize(): bool
    {
        $entry = $this->route('timetableEntry');

        return $entry instanceof TimetableEntry
            && ($this->user()?->can('update', $entry) ?? false);
    }

    /**
     * Get validation rules for an updated timetable entry.
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
