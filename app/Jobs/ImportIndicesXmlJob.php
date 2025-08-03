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

        $xml = simplexml_load_string($this->xmlContent);

        $indices = $this->xmlItemsToArray($xml);

        $livroService->criarIndicesRecursivamente($livro, $indices);
    }

    private function xmlItemsToArray($xmlItems): array
    {
        $result = [];

        foreach ($xmlItems as $item) {
            $node = [
                'titulo' => (string) $item['titulo'],
                'pagina' => (string) $item['pagina'],
                'subindices' => []
            ];

            if ($item->item) {
                $node['subindices'] = $this->xmlItemsToArray($item->item);
            }

            $result[] = $node;
        }

        return $result;
    }
}
