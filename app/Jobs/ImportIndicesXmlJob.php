<?php

namespace App\Jobs;

use App\Models\Livro;
use App\Services\LivroService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportIndicesXmlJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public $livroId,
        public $xmlContent
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(LivroService $livroService): void
    {
        $livro = Livro::findOrFail($this->livroId);

        $xml = @simplexml_load_string($this->xmlContent);

        if ($xml === false) {
            throw new \InvalidArgumentException('Formato XML inválido');
        }

        $indices = $this->xmlItemsToArray($xml);

        $livroService->criarIndicesRecursivamente($livro, $indices);
    }

    /**
     * Converte items XML em um array de índices
     *
     * @param \SimpleXMLElement $xmlItems
     * @return array
     * @throws \InvalidArgumentException quando um item não tem os atributos obrigatórios
     */
    private function xmlItemsToArray(\SimpleXMLElement $xmlItems): array
    {
        $result = [];

        foreach ($xmlItems as $item) {
            if (!isset($item['titulo']) || !isset($item['pagina'])) {
                throw new \InvalidArgumentException('Item XML inválido: todos os itens devem ter os atributos "titulo" e "pagina"');
            }

            $node = [
                'titulo' => (string) $item['titulo'],
                'pagina' => (int) $item['pagina'],
                'subindices' => []
            ];

            if ($item->item && count($item->item) > 0) {
                $node['subindices'] = $this->xmlItemsToArray($item->item);
            }

            $result[] = $node;
        }

        return $result;
    }
}
