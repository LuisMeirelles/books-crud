<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['required'],
            'indices' => ['required', 'array'],
            'indices.*.titulo' => ['required'],
            'indices.*.pagina' => ['required', 'numeric'],
            'indices.*.subindices' => ['array'],
            'indices.*.subindices.*.titulo' => ['required'],
            'indices.*.subindices.*.pagina' => ['required', 'numeric']
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
