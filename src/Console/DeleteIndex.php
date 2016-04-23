<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Elodex\IndexManager;

class DeleteIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:delete-index
                            {--I|index= : The name of the index to delete}
                            {--force : Force deletion without prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an Elasticsearch index';

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
        $force = $this->option('force') ? true : false;

        if ($force || $this->confirm("Do you really want to delete the index '{$indexName}' ? [y|N]")) {
            $this->indexManager->deleteIndex($indexName);

            $this->info("Index '{$indexName}' successfully deleted.");
        }
    }
}
