<?php

namespace App\Services;

use App\Models\Indice;
use App\Models\Livro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LivroService
{
    /**
     * Busca livros pelo título do índice
     *
     * @param string $tituloDoIndice
     * @return Collection
     */
    public function buscarPorTituloDoIndice(string $tituloDoIndice): Collection
    {
        $indices = Indice::where('titulo', 'like', "%{$tituloDoIndice}%")->get();

        $livrosEncontrados = collect();

        foreach ($indices as $indiceEncontrado) {
            $livro = $indiceEncontrado->livro;

            $indicesPaisIds = $indiceEncontrado->indices_pais->pluck('id')->toArray();
            $indicesBuscadosIds = [$indiceEncontrado->id];

            $livro->load(['indices' => function ($query) use ($indicesPaisIds, $indicesBuscadosIds) {
                $query->whereNull('indice_pai_id')
                    ->where(function ($q) use ($indicesPaisIds) {
                        $q->whereIn('id', $indicesPaisIds);
                    })
                    ->with(['subindicesRecursivos' => function ($subQuery) use ($indicesPaisIds, $indicesBuscadosIds) {
                        $this->filtrarSubindicesRecursivos($subQuery, $indicesPaisIds, $indicesBuscadosIds);
                    }]);
            }]);

            $livrosEncontrados->push($livro);
        }

        return $livrosEncontrados->unique('id');
    }

    /**
     * Filtra subíndices recursivamente
     *
     * @param $query
     * @param array $indicesPaisIds
     * @param array $indicesBuscadosIds
     */
    private function filtrarSubindicesRecursivos($query, array $indicesPaisIds, array $indicesBuscadosIds = []): void
    {
        $query->whereIn('id', $indicesPaisIds)
            ->with(['subindicesRecursivos' => function ($subQuery) use ($indicesPaisIds, $indicesBuscadosIds) {
                $parentId = $subQuery->getParentKey();

                if (in_array($parentId, $indicesBuscadosIds)) {
                    $subQuery->whereRaw('1=0');
                } else {
                    $subQuery->whereIn('id', $indicesPaisIds);

                    $this->filtrarSubindicesRecursivos($subQuery, $indicesPaisIds, $indicesBuscadosIds);
                }
            }]);
    }

    /**
     * Busca livros pelo título
     *
     * @param string|null $titulo
     * @return Collection
     */
    public function buscarPorTitulo(?string $titulo = null): Collection
    {
        $livros = Livro::when(
            !empty($titulo),
            fn(Builder $query) => $query->where('titulo', 'like', "%{$titulo}%")
        )->get();

        foreach ($livros as $livro) {
            $livro->load(['indices' => function ($query) {
                $query->whereNull('indice_pai_id')
                    ->with('subindicesRecursivos');
            }]);
        }

        return $livros;
    }

    /**
     * Cria um novo livro com seus índices
     *
     * @param array $params
     * @param int $usuarioId
     * @return Livro
     */
    public function criarLivro(array $params, int $usuarioId): Livro
    {
        $livro = Livro::create([
            'usuario_publicador_id' => $usuarioId,
            'titulo' => $params['titulo'],
        ]);

        $this->criarIndicesRecursivamente($livro, $params['indices']);

        $livro->load(['indices' => function ($query) {
            $query->whereNull('indice_pai_id')->with('subindicesRecursivos');
        }]);

        return $livro;
    }

    /**
     * Cria índices recursivamente para um livro
     *
     * @param Livro $livro
     * @param array $indices
     * @param int|null $indicePaiId
     */
    public function criarIndicesRecursivamente(Livro $livro, array $indices, ?int $indicePaiId = null): void
    {
        foreach ($indices as $indiceData) {
            $indice = $livro->indices()->create([
                'titulo' => $indiceData['titulo'],
                'pagina' => $indiceData['pagina'],
                'indice_pai_id' => $indicePaiId,
            ]);

            if (!empty($indiceData['subindices'])) {
                $this->criarIndicesRecursivamente($livro, $indiceData['subindices'], $indice->id);
            }
        }
    }
}
