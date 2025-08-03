<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportIndicesXmlRequest;
use App\Http\Requests\LivroRequest;
use App\Http\Requests\ListLivrosRequest;
use App\Http\Resources\StoreLivroResource;
use App\Jobs\ImportIndicesXmlJob;
use App\Models\Livro;
use App\Services\LivroService;

class LivroController extends Controller
{
    public function __construct(private readonly LivroService $livroService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ListLivrosRequest $request)
    {
        $params = $request->validated();

        if (isset($params['titulo_do_indice'])) {
            $livros = $this->livroService->buscarPorTituloDoIndice($params['titulo_do_indice']);
            return StoreLivroResource::collection($livros);
        }

        $livros = $this->livroService->buscarPorTitulo($params['titulo'] ?? null);
        return StoreLivroResource::collection($livros);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LivroRequest $request)
    {
        $params = $request->validated();
        $livro = $this->livroService->criarLivro($params, $request->user()->id);

        return new StoreLivroResource($livro);
    }

    public function importIndicesXml(ImportIndicesXmlRequest $request, Livro $livro)
    {
        $realPath = $request->file('xml')->getRealPath();
        $xmlContent = file_get_contents($realPath);

        ImportIndicesXmlJob::dispatch($livro->id, $xmlContent);

        return response()->json(['status' => 'Importação iniciada!']);
    }
}
