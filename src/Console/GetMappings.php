<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Debug\Dumper;
use Illuminate\Support\Arr;
use Elodex\IndexManager;
use Elodex\Contracts\IndexedModel;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class GetMappings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:get-mappings
                            {--C|class= : Indexed model class for which the mappings should received}
                            {--I|index= : Name of the index}
                            {--dump : Print the result as a dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get mappings from the Elasticsearch index';

    /**
     * Index manager instance used for all index operations.
     *
     * @var \Elodex\IndexManager
     */
    protected $indexManager;

    /**
     * Create a new command instance.
     *
     * @param  \Elodex\IndexManager $indexManager
     * @return void
     */
    public function __construct(IndexManager $indexManager)
    {
        parent::__construct();

        $this->indexManager = $indexManager;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function handle()
    {
        $class = $this->option('class');
        $dump = $this->option('dump') ? true : false;
        $indexName = $this->option('index') ?: $this->indexManager->getDefaultIndex();
        $indexTypeName = null;

        if (! empty($class)) {
            if (! class_exists($class)) {
                throw new InvalidArgumentException("Specified class '{$class}' is not valid or does not exist.");
            }

            $model = new $class;
            if (! $model instanceof IndexedModel) {
                throw new InvalidArgumentException("Class '{$class}' is not an indexed model class.");
            }

            $indexTypeName = $model->getIndexTypeName();
        }

        $mappings = $this->indexManager->getMappings($indexName, $indexTypeName);

        if (empty($mappings)) {
            $this->warn('No mappings found.');

            return 1;
        }

        if ($dump) {
            (new Dumper)->dump($mappings);

            return 0;
        }

        if (empty($class)) {
            foreach ($mappings as $data) {
                foreach ($data['mappings'] as $t => $m) {
                    $this->info("Index property mappings for type '{$t}':");

                    $this->printMappings($m['properties']);
                    $this->line('');
                }
            }
        } else {
            $this->info("Index property mappings for model class '{$class}':");

            $this->printMappings(Arr::get($mappings, "{$indexName}.mappings.{$indexTypeName}.properties"));
        }
    }

    /**
     * Print index mappings.
     *
     * @param  array $mappings
     * @return void
     */
    protected function printMappings(array $mappings)
    {
        $headers = ['Property', 'Type', 'Format', 'Analyzer', 'Child properties'];
        $rows = [];

        foreach ($mappings as $property => $mapping) {
            $row = [$property];
            $row[] = Arr::get($mapping, 'type', '');
            $row[] = Arr::get($mapping, 'format', '');
            $row[] = Arr::get($mapping, 'anaylzer', '');

            if (isset($mapping['properties'])) {
                $row[] = wordwrap(implode(', ', array_keys($mapping['properties'])), 30);
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);
    }
}
