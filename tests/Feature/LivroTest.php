<?php

use App\Models\Livro;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

test('usuário pode listar todos os livros', function () {
    Livro::factory()->count(3)->create([
        'usuario_publicador_id' => $this->user->id
    ]);
    $response = $this->getJson('/v1/livros');
    $response->assertStatus(200)
             ->assertJsonCount(3);
});

test('usuário pode buscar livros por título', function () {
    Livro::factory()->create([
        'titulo' => 'PHP para iniciantes',
        'usuario_publicador_id' => $this->user->id
    ]);

    Livro::factory()->create([
        'titulo' => 'Laravel Avançado',
        'usuario_publicador_id' => $this->user->id
    ]);
    $response = $this->getJson('/v1/livros?titulo=PHP');
    $response->assertStatus(200)
             ->assertJsonCount(1)
             ->assertJsonFragment(['titulo' => 'PHP para iniciantes']);
});

test('usuário pode buscar livros por título do índice', function () {
    $livro = Livro::factory()->create([
        'titulo' => 'Guia Completo de Programação',
        'usuario_publicador_id' => $this->user->id
    ]);
    $indiceRaiz = $livro->indices()->create([
        'titulo' => 'Introdução',
        'pagina' => 1
    ]);
    $subindice = $livro->indices()->create([
        'titulo' => 'PHP Básico',
        'pagina' => 10,
        'indice_pai_id' => $indiceRaiz->id
    ]);
    $response = $this->getJson('/v1/livros?titulo_do_indice=PHP');
    $response->assertStatus(200)
             ->assertJsonFragment(['titulo' => 'Guia Completo de Programação'])
             ->assertJsonFragment(['titulo' => 'PHP Básico']);
});

test('usuário pode criar um novo livro com índices', function () {
    $livroData = [
        'titulo' => 'Meu Novo Livro',
        'indices' => [
            [
                'titulo' => 'Capítulo 1',
                'pagina' => 1,
                'subindices' => [
                    [
                        'titulo' => 'Subcapítulo 1.1',
                        'pagina' => 2,
                        'subindices' => []
                    ]
                ]
            ],
            [
                'titulo' => 'Capítulo 2',
                'pagina' => 15,
                'subindices' => []
            ]
        ]
    ];
    $response = $this->postJson('/v1/livros', $livroData);
    $response->assertStatus(201)
             ->assertJsonFragment(['titulo' => 'Meu Novo Livro'])
             ->assertJsonFragment(['titulo' => 'Capítulo 1'])
             ->assertJsonFragment(['titulo' => 'Subcapítulo 1.1'])
             ->assertJsonFragment(['titulo' => 'Capítulo 2']);
    $this->assertDatabaseHas('livros', ['titulo' => 'Meu Novo Livro']);
    $this->assertDatabaseHas('indices', ['titulo' => 'Capítulo 1']);
    $this->assertDatabaseHas('indices', ['titulo' => 'Subcapítulo 1.1']);
    $this->assertDatabaseHas('indices', ['titulo' => 'Capítulo 2']);
});

test('usuário pode importar índices a partir de um arquivo XML', function () {
    $livro = Livro::factory()->create([
        'titulo' => 'Livro para Importação',
        'usuario_publicador_id' => $this->user->id
    ]);
    $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<indices>
    <item titulo="Capítulo Importado 1" pagina="5">
        <item titulo="Subcapítulo Importado 1.1" pagina="6"/>
    </item>
    <item titulo="Capítulo Importado 2" pagina="20"/>
</indices>
XML;

    $file = UploadedFile::fake()->createWithContent('indices.xml', $xmlContent);
    $response = $this->postJson("/v1/livros/{$livro->id}/importar-indices-xml", [
        'xml' => $file
    ]);
    $response->assertStatus(200)
             ->assertJson(['status' => 'Importação iniciada!']);
    \App\Jobs\ImportIndicesXmlJob::dispatchSync($livro->id, $xmlContent);
    $this->assertDatabaseHas('indices', [
        'titulo' => 'Capítulo Importado 1',
        'livro_id' => $livro->id
    ]);

    $this->assertDatabaseHas('indices', [
        'titulo' => 'Subcapítulo Importado 1.1',
        'livro_id' => $livro->id
    ]);

    $this->assertDatabaseHas('indices', [
        'titulo' => 'Capítulo Importado 2',
        'livro_id' => $livro->id
    ]);
});
