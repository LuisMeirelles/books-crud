<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LivroRequest;
use App\Http\Requests\ListLivrosRequest;
use App\Http\Resources\StoreLivroResource;
use App\Models\Indice;
use App\Models\Livro;
use Illuminate\Database\Eloquent\Builder;

class LivroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListLivrosRequest $request)
    {
        $params = $request->validated();

        if (isset($params['titulo_do_indice'])) {
            return $this->buscarPorTituloDoIndice($params['titulo_do_indice']);
        }

        return StoreLivroResource::collection(Livro::when(
            isset($params['titulo']),
            fn(Builder $query) => $query->where('titulo', 'like', "%{$params['titulo']}%")
        )->get());
    }

    private function buscarPorTituloDoIndice(string $tituloDoIndice)
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

        return StoreLivroResource::collection($livrosEncontrados->unique('id'));
    }

    private function filtrarSubindicesRecursivos($query, array $indicesPaisIds, array $indicesBuscadosIds = [])
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
     * Store a newly created resource in storage.
     */
    public function store(LivroRequest $request)
    {
        $params = $request->validated();

        $livro = Livro::create([
            'usuario_publicador_id' => $request->user()->id,
            'titulo' => $params['titulo'],
        ]);

        $this->criarIndicesRecursivamente($livro, $params['indices']);

        $livro->load(['indices' => function ($query) {
            $query->whereNull('indice_pai_id')->with('subindicesRecursivos');
        }]);

        return new StoreLivroResource($livro);
    }

    private function criarIndicesRecursivamente($livro, $indices, $indicePaiId = null)
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
