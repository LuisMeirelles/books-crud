<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RecursiveIndicesRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('O campo :attribute deve ser um array.');
            return;
        }

        $this->validateIndicesArray($value, $attribute, $fail);
    }

    private function validateIndicesArray(array $indices, string $basePath, Closure $fail): void
    {
        foreach ($indices as $index => $indice) {
            $currentPath = "{$basePath}.{$index}";

            if (empty($indice['titulo'])) {
                $fail("O campo $currentPath.titulo é obrigatório.");
            }

            if (!isset($indice['pagina'])) {
                $fail("O campo $currentPath.pagina é obrigatório.");
            } elseif (!is_numeric($indice['pagina'])) {
                $fail("O campo $currentPath.pagina deve ser numérico.");
            }

            if (isset($indice['subindices'])) {
                if (!is_array($indice['subindices'])) {
                    $fail("O campo $currentPath.subindices deve ser um array.");
                } else {
                    $this->validateIndicesArray($indice['subindices'], "$currentPath.subindices", $fail);
                }
            }
        }
    }
}
