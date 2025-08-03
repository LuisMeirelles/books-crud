<?php

use App\Jobs\ImportIndicesXmlJob;
use App\Models\Livro;
use App\Models\User;
use App\Services\LivroService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->livro = Livro::factory()->create([
        'usuario_publicador_id' => $this->user->id
    ]);
});

test('job processa corretamente o XML e cria os índices', function () {
    $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<indices>
    <item titulo="Capítulo XML" pagina="1">
        <item titulo="Subcapítulo XML" pagina="2">
            <item titulo="Tópico XML" pagina="3" />
        </item>
        <item titulo="Outro Subcapítulo" pagina="4" />
    </item>
</indices>
XML;
    $job = new ImportIndicesXmlJob($this->livro->id, $xmlContent);
    $livroServiceMock = Mockery::mock(LivroService::class);
    $livroServiceMock->shouldReceive('criarIndicesRecursivamente')
        ->once()
        ->with(
            Mockery::on(function ($livro) {
                return $livro->id === $this->livro->id;
            }),
            Mockery::on(function ($indices) {
                return count($indices) === 1 &&
                       $indices[0]['titulo'] === 'Capítulo XML' &&
                       $indices[0]['pagina'] === 1 &&
                       count($indices[0]['subindices']) === 2 &&
                       $indices[0]['subindices'][0]['titulo'] === 'Subcapítulo XML' &&
                       count($indices[0]['subindices'][0]['subindices']) === 1 &&
                       $indices[0]['subindices'][0]['subindices'][0]['titulo'] === 'Tópico XML' &&
                       $indices[0]['subindices'][1]['titulo'] === 'Outro Subcapítulo';
            })
        )
        ->andReturn(null);
    $job->handle($livroServiceMock);
});

test('job converte corretamente itens XML para array de índices', function () {
    $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<indices>
    <item titulo="Item 1" pagina="10" />
    <item titulo="Item 2" pagina="20" />
</indices>
XML;

    $xml = simplexml_load_string($xmlContent);
    $job = new ImportIndicesXmlJob($this->livro->id, $xmlContent);
    $reflection = new ReflectionClass($job);
    $method = $reflection->getMethod('xmlItemsToArray');

    $result = $method->invoke($job, $xml->item);
    expect($result)->toBeArray()
        ->and($result)->toHaveCount(2)
        ->and($result[0]['titulo'])->toBe('Item 1')
        ->and($result[0]['pagina'])->toBe(10)
        ->and($result[1]['titulo'])->toBe('Item 2')
        ->and($result[1]['pagina'])->toBe(20);
});

test('job lida corretamente com XML de estrutura aninhada complexa', function () {
    $xmlContent = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<indices>
    <item titulo="Nível 1" pagina="1">
        <item titulo="Nível 2.1" pagina="2">
            <item titulo="Nível 3.1" pagina="3">
                <item titulo="Nível 4" pagina="4" />
            </item>
        </item>
        <item titulo="Nível 2.2" pagina="5">
            <item titulo="Nível 3.2" pagina="6" />
        </item>
    </item>
</indices>
XML;
    $job = new ImportIndicesXmlJob($this->livro->id, $xmlContent);
    $livroServiceMock = Mockery::mock(LivroService::class);
    $livroServiceMock->shouldReceive('criarIndicesRecursivamente')->once()->andReturn(null);
    $job->handle($livroServiceMock);
    $this->assertTrue(true);
});

test('job lança exceção quando o XML é inválido', function () {
    $xmlInvalido = 'Este não é um XML válido';
    $job = new ImportIndicesXmlJob($this->livro->id, $xmlInvalido);
    $livroServiceMock = Mockery::mock(LivroService::class);
    expect(fn() => $job->handle($livroServiceMock))->toThrow(\Exception::class);
});

test('job lança exceção quando o livro não existe', function () {
    $livroIdInexistente = 9999;
    $xmlContent = '<indices><item titulo="Teste" pagina="1" /></indices>';
    $job = new ImportIndicesXmlJob($livroIdInexistente, $xmlContent);
    $livroServiceMock = Mockery::mock(LivroService::class);
    expect(fn() => $job->handle($livroServiceMock))->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
