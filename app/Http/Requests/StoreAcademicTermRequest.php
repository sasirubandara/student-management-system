<?php

namespace App\Http\Requests;

use App\Models\AcademicTerm;
use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            AcademicTerm::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => [
                'prohibited',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'term_number' => [
                'required',
                'integer',
                'between:1,255',
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
