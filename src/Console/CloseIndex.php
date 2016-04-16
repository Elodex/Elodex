<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Elodex\IndexManager;

class CloseIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:close-index
                            {--I|index= : The name of the opened index to close}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close an opened index';

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

        $results = $this->indexManager->closeIndex($indexName);

        $this->info("Index '{$indexName}' successfully closed.");
    }
}
