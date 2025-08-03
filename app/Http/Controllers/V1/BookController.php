<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BookRequest;
use App\Http\Requests\ListBooksRequest;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListBooksRequest $request)
    {
        $params = $request->validated();

        return Book::when(
            isset($params['titulo']),
            fn(Builder $query) => $query->where('titulo', $params['titulo'])
        )->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    {
        $params = $request->validated();

        return Book::create([
            'usuario_publicador_id' => $request->user()->id,
            'titulo' => $params['titulo'],
        ]);
    }
}
