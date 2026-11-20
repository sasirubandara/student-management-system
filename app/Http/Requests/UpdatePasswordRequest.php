<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Store password validation errors separately.
     */
    protected $errorBag = 'updatePassword';

    /**
     * Determine whether the user can change their password.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    /**
     * Get the password validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],

            'password' => [
                'required',
                'string',
                'confirmed',
                'different:current_password',
                Password::min(12),
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => 'The current password is incorrect.',

            'password.different' => 'Your new password must be different from your current password.',

            'password.confirmed' => 'The new password confirmation does not match.',
        ];
    }

    /**
     * Get custom validation attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => 'current password',
            'password' => 'new password',
        ];
    }
}
