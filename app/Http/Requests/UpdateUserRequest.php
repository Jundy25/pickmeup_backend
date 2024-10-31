<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'gender' => 'required|string',
            'date_of_birth' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->user->user_id),
            ],
            'user_name' => [
                'required',
                'string',
                Rule::unique('users')->ignore($this->user->user_id),
            ],
            'password' => 'nullable|string|min:6',
            'mobile_number' => 'required|string',
            'country' => 'required|string',
        ];
    }
}
