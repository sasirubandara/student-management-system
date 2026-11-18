<?php

namespace App\Http\Requests;

use App\Models\ApplicationSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationSettingRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(
            'manage',
            ApplicationSetting::class
        ) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'institution_name' => [
                'required',
                'string',
                'max:255',
            ],

            'institution_email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'institution_phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'institution_address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'current_academic_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'timezone' => [
                'required',
                'string',
                'max:100',
                'timezone',
            ],

            'date_format' => [
                'required',
                'string',
                Rule::in(ApplicationSetting::DATE_FORMATS),
            ],
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'institution_name' => 'institution name',
            'institution_email' => 'institution email',
            'institution_phone' => 'institution phone number',
            'institution_address' => 'institution address',
            'current_academic_year' => 'current academic year',
            'timezone' => 'timezone',
            'date_format' => 'date format',
        ];
    }
}
