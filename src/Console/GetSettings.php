<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Debug\Dumper;
use Elodex\IndexManager;
use Carbon\Carbon;

class GetSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:get-settings
                            {--I|index= : Name of the index}
                            {--dump : Print the result as a dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get settings from the Elasticsearch index';

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

        $settings = $this->indexManager->getSettings($indexName);

        if ($dump) {
            (new Dumper)->dump($settings);
        } else {
            $this->line("Settings for index '{$indexName}':");
            $this->line('');

            $this->printSettings(Arr::get($settings, "{$indexName}.settings.index"));
        }
    }

    /**
     * Print the settings.
     *
     * @param  array $settings
     * @return void
     */
    protected function printSettings(array $settings)
    {
        if ($creationDate = Arr::get($settings, 'creation_date')) {
            $this->line('     <info>Creation date</info>: '.Carbon::createFromTimestamp($creationDate / 1000));
        }

        if ($numberOfShards = Arr::get($settings, 'number_of_shards')) {
            $this->line("  <info>Number of shards</info>: {$numberOfShards}");
        }

        if ($numberOfReplicas = Arr::get($settings, 'number_of_replicas')) {
            $this->line("<info>Number of replicas</info>: {$numberOfReplicas}");
        }

        if ($uuid = Arr::get($settings, 'uuid')) {
            $this->line("              <info>UUID</info>: {$uuid}");
        }

        if ($analyzers = Arr::get($settings, 'analysis.analyzer')) {
            $this->line('');
            $this->printAnalyzers($analyzers);
        }
    }

    /**
     * Print the global analyzers.
     *
     * @param  array $analyzers
     * @return void
     */
    protected function printAnalyzers(array $analyzers)
    {
        $headers = ['Analyzer', 'Type', 'Filter', 'Char Filter', 'Tokenizer'];
        $rows = [];

        foreach ($analyzers as $name => $data) {
            $row = [$name];
            $row[] = Arr::get($data, 'type', '');
            $row[] = implode(',', Arr::get($data, 'filter', []));
            $row[] = implode(',', Arr::get($data, 'char_filter', []));
            $row[] = Arr::get($data, 'tokenizer', '');

            $rows[] = $row;
        }

        $this->table($headers, $rows);
        $this->line('');
    }
}
