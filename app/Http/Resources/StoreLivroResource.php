<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreLivroResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usuarioPublicador = $this->usuarioPublicador;

        return [
            'titulo' => $this->titulo,
            'indices' => IndiceResource::collection($this->indices),
            'usuario_publicador' => [
                'id' => $usuarioPublicador->id,
                'nome' => $usuarioPublicador->name,
            ],
        ];
    }
}
