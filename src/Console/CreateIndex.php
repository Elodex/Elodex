<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Elodex\IndexManager;

class CreateIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:create-index
                            {--I|index= : The name of the index to create}
                            {--S|shards= : Number of shards}
                            {--R|replicas= : Number of replicas}
                            {--F|reset : Reset index if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an Elasticsearch index';

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
     */
    public function handle()
    {
        $indexName = $this->option('index') ?: $this->indexManager->getDefaultIndex();
        $reset = $this->option('reset') ?: false;

        if ($this->indexManager->indicesExist([$indexName])) {
            if ($reset) {
                $this->resetIndex($indexName);
            } else {
                $this->error("Index '{$indexName}' already exists, exiting. Use --reset to force the creation of a new index.");

                return 1;
            }
        }

        $settings = [];
        $shards = $this->option('shards');
        if (! is_null($shards)) {
            $settings['number_of_shards'] = $shards;
        }

        $replicas = $this->option('replicas');
        if (! is_null($replicas)) {
            $settings['number_of_replicas'] = $replicas;
        }

        $this->createIndex($indexName, $settings);

        $this->info("Index '{$indexName}' successfully created.");
    }

    /**
     * Reset (delete) an existing index.
     *
     * @param  string $indexName
     * @return void
     */
    protected function resetIndex($indexName)
    {
        $this->indexManager->deleteIndex($indexName);
    }

    /**
     * Create a new index.
     *
     * @param  string $indexName
     * @param  array $settings
     * @return void
     */
    protected function createIndex($indexName, $settings)
    {
        $this->indexManager->createIndex($indexName, $settings);
    }
}
