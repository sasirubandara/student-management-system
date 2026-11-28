<?php

namespace App\Http\Requests;

use App\Models\StudentPromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentPromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            StudentPromotion::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'source_enrollment_id' => [
                'required',
                'integer',
                Rule::exists('student_enrollments', 'id'),
            ],
            'destination_class_id' => [
                'required',
                'integer',
                Rule::exists('academic_classes', 'id'),
            ],
            'promotion_type' => [
                'required',
                Rule::in([
                    StudentPromotion::TYPE_ADVANCED,
                    StudentPromotion::TYPE_REPEATED,
                ]),
            ],
            'promotion_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }
}
