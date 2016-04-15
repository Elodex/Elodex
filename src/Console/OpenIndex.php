<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Elodex\IndexManager;

class OpenIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:open-index
                            {--I|index= : The name of the closed index to open}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Open a closed index';

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
        $indexName = $this->option('index') ? : $this->indexManager->getDefaultIndex();

        $results = $this->indexManager->openIndex($indexName);

        $this->info("Index '{$indexName}' successfully opened.");
    }
}

