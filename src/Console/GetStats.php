<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Debug\Dumper;
use Elodex\IndexManager;

class GetStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:get-stats
                            {--I|index= : Name of the index}
                            {--dump : Print the result as a dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get stats from the Elasticsearch index';

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
        $dump = $this->option('dump') ? true : false;

        $stats = $this->indexManager->stats($indexName);

        if ($dump) {
            (new Dumper)->dump($stats);
        } else {
            $this->printStats($stats);
        }
    }

    /**
     * Print the stats.
     *
     * @param  array stats
     * @return void
     */
    public function printStats(array $stats)
    {
        $shardStats = $stats['_shards'];
        $this->info('Shards');
        $this->line("  {$shardStats['total']} total, {$shardStats['successful']} successful, {$shardStats['failed']} failed");

        $allIndices = $stats['_all'];
        $this->info('All Indices');

        $totalStats = $allIndices['total'];

        if ($docsStats = Arr::get($totalStats, 'docs')) {
            $this->line("  Documents:   {$docsStats['count']} total, {$docsStats['deleted']} deleted");
        }

        if ($storeStats = Arr::get($totalStats, 'store')) {
            $this->line("  Store:       {$storeStats['size_in_bytes']} Bytes");
        }

        if ($searchStats = Arr::get($totalStats, 'search')) {
            $this->line("  Search:      {$searchStats['query_total']} queries, {$searchStats['query_time_in_millis']} ms");
        }

        if ($cacheStats = Arr::get($totalStats, 'query_cache')) {
            $this->line("  Query Cache: {$cacheStats['hit_count']} hits, {$cacheStats['miss_count']} misses, {$cacheStats['memory_size_in_bytes']} Bytes");
        }

        $this->line('');
    }
}
