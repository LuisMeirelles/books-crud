<?php

use App\Models\Livro;
use App\Models\Indice;
use App\Models\User;
use App\Services\LivroService;

beforeEach(function () {
    $this->livroService = new LivroService();
    $this->user = User::factory()->create();
});

test('pode criar um livro com índices', function () {
    $params = [
        'titulo' => 'Livro de Teste',
        'indices' => [
            [
                'titulo' => 'Índice 1',
                'pagina' => 10,
                'subindices' => [
                    [
                        'titulo' => 'Subíndice 1.1',
                        'pagina' => 11,
                        'subindices' => []
                    ]
                ]
            ]
        ]
    ];

    $livro = $this->livroService->criarLivro($params, $this->user->id);

    expect($livro->titulo)->toBe('Livro de Teste')
        ->and($livro->usuario_publicador_id)->toBe($this->user->id)
        ->and($livro->indices)->toHaveCount(1)
        ->and($livro->indices->first()->titulo)->toBe('Índice 1')
        ->and($livro->indices->first()->pagina)->toBe(10)
        ->and($livro->indices->first()->subindicesRecursivos)->toHaveCount(1)
        ->and($livro->indices->first()->subindicesRecursivos->first()->titulo)->toBe('Subíndice 1.1');
});

test('pode buscar livros por título', function () {
    Livro::factory()->create(['titulo' => 'Livro de PHP']);
    Livro::factory()->create(['titulo' => 'Livro de Laravel']);
    Livro::factory()->create(['titulo' => 'Outro livro']);

    $resultado = $this->livroService->buscarPorTitulo('Livro de');

    expect($resultado)->toHaveCount(2)
        ->and($resultado->pluck('titulo')->toArray())->toContain('Livro de PHP')
        ->and($resultado->pluck('titulo')->toArray())->toContain('Livro de Laravel');
});

test('pode buscar livros por título do índice', function () {
    $livro1 = Livro::factory()->create(['titulo' => 'Livro 1']);
    $livro2 = Livro::factory()->create(['titulo' => 'Livro 2']);

    $indice1 = $livro1->indices()->create([
        'titulo' => 'Índice Especial',
        'pagina' => 5
    ]);

    $indice2 = $livro2->indices()->create([
        'titulo' => 'Outro Índice',
        'pagina' => 10
    ]);

    $resultado = $this->livroService->buscarPorTituloDoIndice('Especial');

    expect($resultado)->toHaveCount(1)
        ->and($resultado->first()->titulo)->toBe('Livro 1');
});

test('pode criar índices recursivamente', function () {
    $livro = Livro::factory()->create();
    $indices = [
        [
            'titulo' => 'Nível 1',
            'pagina' => 1,
            'subindices' => [
                [
                    'titulo' => 'Nível 2',
                    'pagina' => 2,
                    'subindices' => [
                        [
                            'titulo' => 'Nível 3',
                            'pagina' => 3,
                            'subindices' => []
                        ]
                    ]
                ]
            ]
        ]
    ];

    $this->livroService->criarIndicesRecursivamente($livro, $indices);

    $indiceNivel1 = Indice::where('livro_id', $livro->id)
        ->where('titulo', 'Nível 1')
        ->whereNull('indice_pai_id')
        ->first();

    expect($indiceNivel1)->not->toBeNull();

    $indiceNivel2 = Indice::where('livro_id', $livro->id)
        ->where('titulo', 'Nível 2')
        ->where('indice_pai_id', $indiceNivel1->id)
        ->first();

    expect($indiceNivel2)->not->toBeNull();

    $indiceNivel3 = Indice::where('livro_id', $livro->id)
        ->where('titulo', 'Nível 3')
        ->where('indice_pai_id', $indiceNivel2->id)
        ->first();

    expect($indiceNivel3)->not->toBeNull();
});

test('ao buscar por índice, apenas os pais do índice encontrado são retornados', function () {
    $livro = Livro::factory()->create(['titulo' => 'Livro Teste']);

    $indiceRaiz = $livro->indices()->create([
        'titulo' => 'Capítulo 1',
        'pagina' => 1
    ]);

    $indiceNivel2 = $livro->indices()->create([
        'titulo' => 'Seção 1.1',
        'pagina' => 2,
        'indice_pai_id' => $indiceRaiz->id
    ]);

    $indiceAlvo = $livro->indices()->create([
        'titulo' => 'Tópico Específico',
        'pagina' => 3,
        'indice_pai_id' => $indiceNivel2->id
    ]);

    $outroIndiceNivel3 = $livro->indices()->create([
        'titulo' => 'Outro Tópico',
        'pagina' => 4,
        'indice_pai_id' => $indiceNivel2->id
    ]);

    $resultado = $this->livroService->buscarPorTituloDoIndice('Específico');

    $livroEncontrado = $resultado->first();
    $indicesCarregados = $livroEncontrado->indices;

    expect($indicesCarregados)->toHaveCount(1)
        ->and($indicesCarregados[0]->titulo)->toBe('Capítulo 1');

    $subindicesNivel1 = $indicesCarregados[0]->subindicesRecursivos;
    expect($subindicesNivel1)->toHaveCount(1)
        ->and($subindicesNivel1[0]->titulo)->toBe('Seção 1.1');

    $subindicesNivel2 = $subindicesNivel1[0]->subindicesRecursivos;
    expect($subindicesNivel2)->toHaveCount(1)
        ->and($subindicesNivel2[0]->titulo)->toBe('Tópico Específico')
        ->and($subindicesNivel2->pluck('titulo')->toArray())->not->toContain('Outro Tópico');
});
