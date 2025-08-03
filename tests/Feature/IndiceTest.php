<?php

use App\Models\Livro;
use App\Models\User;
use App\Services\LivroService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
    $this->livroService = new LivroService();
    $this->livro = Livro::factory()->create([
        'usuario_publicador_id' => $this->user->id,
        'titulo' => 'Livro de Teste para Índices'
    ]);
});

test('índices são carregados com a estrutura correta', function () {
    $indiceRaiz = $this->livro->indices()->create([
        'titulo' => 'Capítulo 1',
        'pagina' => 1
    ]);

    $subindice1 = $this->livro->indices()->create([
        'titulo' => 'Seção 1.1',
        'pagina' => 2,
        'indice_pai_id' => $indiceRaiz->id
    ]);

    $subindice2 = $this->livro->indices()->create([
        'titulo' => 'Seção 1.2',
        'pagina' => 10,
        'indice_pai_id' => $indiceRaiz->id
    ]);

    $subsubindice = $this->livro->indices()->create([
        'titulo' => 'Subseção 1.1.1',
        'pagina' => 5,
        'indice_pai_id' => $subindice1->id
    ]);
    $response = $this->getJson('/v1/livros');
    $response->assertStatus(200)
             ->assertJsonCount(1)
             ->assertJsonPath('0.titulo', 'Livro de Teste para Índices')
             ->assertJsonPath('0.indices.0.titulo', 'Capítulo 1')
             ->assertJsonPath('0.indices.0.subindices.0.titulo', 'Seção 1.1')
             ->assertJsonPath('0.indices.0.subindices.1.titulo', 'Seção 1.2')
             ->assertJsonPath('0.indices.0.subindices.0.subindices.0.titulo', 'Subseção 1.1.1');
});

test('busca por título de índice retorna apenas a estrutura relacionada', function () {
    $indiceRaiz1 = $this->livro->indices()->create([
        'titulo' => 'Capítulo 1',
        'pagina' => 1
    ]);

    $subindice1 = $this->livro->indices()->create([
        'titulo' => 'Seção Específica',
        'pagina' => 2,
        'indice_pai_id' => $indiceRaiz1->id
    ]);

    $subsubindice1 = $this->livro->indices()->create([
        'titulo' => 'Subseção 1.1.1',
        'pagina' => 3,
        'indice_pai_id' => $subindice1->id
    ]);

    $subindice2 = $this->livro->indices()->create([
        'titulo' => 'Seção 1.2',
        'pagina' => 5,
        'indice_pai_id' => $indiceRaiz1->id
    ]);

    $indiceRaiz2 = $this->livro->indices()->create([
        'titulo' => 'Capítulo 2',
        'pagina' => 20
    ]);
    $response = $this->getJson('/v1/livros?titulo_do_indice=Específica');
    $response->assertStatus(200)
             ->assertJsonCount(1)
             ->assertJsonPath('0.titulo', 'Livro de Teste para Índices')
             ->assertJsonPath('0.indices.0.titulo', 'Capítulo 1')
             ->assertJsonPath('0.indices.0.subindices.0.titulo', 'Seção Específica');
    $responseJson = $response->json();
    $subindices = $responseJson[0]['indices'][0]['subindices'];
    expect(count($subindices))->toBe(1);
    $containsSecao12 = false;
    foreach ($subindices as $subindice) {
        if ($subindice['titulo'] === 'Seção 1.2') {
            $containsSecao12 = true;
            break;
        }
    }

    expect($containsSecao12)->toBeFalse();
    $indicesRaiz = $responseJson[0]['indices'];
    $containsCapitulo2 = false;
    foreach ($indicesRaiz as $indice) {
        if ($indice['titulo'] === 'Capítulo 2') {
            $containsCapitulo2 = true;
            break;
        }
    }

    expect($containsCapitulo2)->toBeFalse();
});

test('o método getIndicesPaisAttribute retorna o caminho correto até o índice', function () {
    $nivel1 = $this->livro->indices()->create([
        'titulo' => 'Nível 1',
        'pagina' => 1
    ]);

    $nivel2 = $this->livro->indices()->create([
        'titulo' => 'Nível 2',
        'pagina' => 2,
        'indice_pai_id' => $nivel1->id
    ]);

    $nivel3 = $this->livro->indices()->create([
        'titulo' => 'Nível 3',
        'pagina' => 3,
        'indice_pai_id' => $nivel2->id
    ]);

    $nivel4 = $this->livro->indices()->create([
        'titulo' => 'Nível 4',
        'pagina' => 4,
        'indice_pai_id' => $nivel3->id
    ]);
    $indicesPais = $nivel4->indices_pais;
    expect($indicesPais)->toHaveCount(4)
        ->and($indicesPais[0]->titulo)->toBe('Nível 1')
        ->and($indicesPais[1]->titulo)->toBe('Nível 2')
        ->and($indicesPais[2]->titulo)->toBe('Nível 3')
        ->and($indicesPais[3]->titulo)->toBe('Nível 4');
});
