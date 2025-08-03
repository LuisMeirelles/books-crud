<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LivroRequest;
use App\Http\Requests\ListLivrosRequest;
use App\Http\Resources\StoreLivroResource;
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

        return Livro::when(
            isset($params['titulo']),
            fn(Builder $query) => $query->where('titulo', $params['titulo'])
        )->get();
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
