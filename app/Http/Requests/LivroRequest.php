<?php

namespace App\Http\Requests;

use App\Rules\RecursiveIndicesRule;
use Illuminate\Foundation\Http\FormRequest;

class LivroRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'titulo' => ['required'],
            'indices' => ['required', 'array', new RecursiveIndicesRule],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
