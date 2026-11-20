<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine whether the user can update their profile.
     */
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    /**
     * Get the validation rules for a profile update.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->user();

        $emailChanged = $this->input('email') !== $user->email;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],

            'current_password' => $emailChanged
                ? ['required', 'string', 'current_password:web']
                : ['nullable', 'string'],
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
            'current_password.required' => 'Your current password is required to change your email address.',

            'current_password.current_password' => 'The current password is incorrect.',

            'email.unique' => 'This email address is already associated with another account.',
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
            'name' => 'full name',
            'email' => 'email address',
            'current_password' => 'current password',
        ];
    }
}
