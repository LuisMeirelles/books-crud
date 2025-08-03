<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthTokenRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'O campo de e-mail é obrigatório',
            'email.email' => 'Por favor, forneça um endereço de e-mail válido',
            'password.required' => 'O campo de senha é obrigatório',
        ];
    }
}
