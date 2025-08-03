<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LivroRequest;
use App\Http\Requests\ListLivrosRequest;
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

        return Livro::create([
            'usuario_publicador_id' => $request->user()->id,
            'titulo' => $params['titulo'],
        ]);
    }
}
