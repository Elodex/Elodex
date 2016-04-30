<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Debug\Dumper;
use Elodex\IndexManager;

class Upgrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:upgrade
                            {--I|index= : Comma separated list of index names}
                            {--W|wait : Wait for the operation to complete}
                            {--dump : Print the result as a dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an upgrade on the index';

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
        $indexName = $this->option('index') ?: '_all';
        $wait = $this->option('wait') ? true : false;
        $dump = $this->option('dump') ? true : false;

        $this->indexManager->closeIndex($indexName);
        $results = $this->indexManager->upgrade($indexName, $wait);
        $this->indexManager->openIndex($indexName);

        if ($dump) {
            (new Dumper)->dump($results);
        } else {
            $this->printResults($results);
        }
    }

    /**
     * Print results.
     *
     * @param  array $results
     * @return void
     */
    protected function printResults(array $results)
    {
        $this->info('Upgrade results');

        $shards = $results['_shards'];
        $this->line("  Shards: {$shards['total']} total, {$shards['successful']} successful, {$shards['failed']} failed");

        $indices = $results['upgraded_indices'];
        if (! empty($indices)) {
            $this->line('  Upgraded indices: ', implode(', ', $indices));
        } else {
            $this->line('  Upgraded indices: <no indices upgraded>');
        }

        $this->line('');
    }
}
