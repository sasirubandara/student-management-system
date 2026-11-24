<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'update',
            $this->route('academicYear')
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'year' => [
                'required',
                'integer',
                'between:2000,2100',
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
        ];
    }
}
