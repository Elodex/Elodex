<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Debug\Dumper;
use Elodex\IndexManager;

class Analyze extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:analyze
                            {analyzer : Analyzer used to analyze the given text}
                            {text : Text to analyze}
                            {--F|filter= : A comma-separated list of filters to use for the analysis}
                            {--T|tokenizer= : The name of the tokenizer to use for the analysis}
                            {--I|index= : Name of the index}
                            {--dump : Print the result as a dump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze a given text with the specified analyzer';

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
        $analyzer = $this->argument('analyzer');
        $text = $this->argument('text');

        $indexName = $this->option('index') ? : $this->indexManager->getDefaultIndex();
        $dump = $this->option('dump') ? true : false;

        $filters = $this->option('filter') ? explode(',', $this->option('filter')) : [];
        $tokenizer = $this->option('tokenizer');

        $results = $this->indexManager->analyze($analyzer, $text, $filters, $tokenizer, $indexName);

        if ($dump) {
            (new Dumper)->dump($results);
        } else {
            $this->printTokens($results['tokens']);
        }
    }

    /**
     * Print the tokens.
     *
     * @param  array $tokens
     * @return void
     */
    protected function printTokens(array $tokens)
    {
        $headers = ['Token', 'Start offset', 'End offset', 'Type', 'Position'];

        $this->table($headers, $tokens);
    }
}
