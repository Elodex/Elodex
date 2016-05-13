<?php

namespace Elodex\Console;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Str;
use Elodex\IndexManager;
use Elodex\Contracts\IndexedModel as IndexedModelContract;

class CreateIndex extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:create-index
                            {--I|index= : The name of the index to create}
                            {--S|shards= : Number of shards}
                            {--R|replicas= : Number of replicas}
                            {--models= : Comma separated list of indexed model classes used for property mappings}
                            {--reset : Reset index if it already exists}
                            {--force : Force the operation to run when in production}';

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

        // Check if the specified index already exists.
        if ($this->indexManager->indicesExist([$indexName])) {
            if ($reset && $this->confirmToProceed()) {
                $this->resetIndex($indexName);
            } else {
                $this->error("Index '{$indexName}' already exists, exiting. Use --reset to replace an existing new index.");

                return 1;
            }
        }

        // Build the settings array.
        $settings = [];
        $shards = $this->option('shards');
        if (! is_null($shards)) {
            $settings['number_of_shards'] = $shards;
        }

        $replicas = $this->option('replicas');
        if (! is_null($replicas)) {
            $settings['number_of_replicas'] = $replicas;
        }

        $models = [];
        if ($this->option('models')) {
            $models = explode(',', trim($this->option('models')));

            if (($models = $this->parseModelsOption($models)) === false) {
                return 1;
            }
        }

        $this->createIndex($indexName, $settings, $models);

        $this->info("Index '{$indexName}' successfully created.");
    }

    /**
     * Checks if the specified models are valid indexed model classes and builds
     * an array of fully qualified class names.
     *
     * @param  array $models
     * @return bool|array
     */
    protected function parseModelsOption(array $models)
    {
        $parsed = [];

        foreach ($models as $model) {
            $class = $this->parseModelName(trim($model));

            if (! class_exists($class)) {
                $this->error("Model class '{$class}' does not exist!");

                return false;
            }

            if (! ((new $class) instanceof IndexedModelContract)) {
                $this->error("Model class '{$class}' does not implement the '".IndexedModelContract::class."' interface!");

                return false;
            }

            $parsed[] = $class;
        }

        return $parsed;
    }

    /**
     * Parse the model name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function parseModelName($name)
    {
        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        if (Str::contains($name, '/')) {
            $name = str_replace('/', '\\', $name);
        }

        return $this->parseModelName(trim($rootNamespace, '\\').'\\'.$name);
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
     * @param  array $models
     * @return void
     */
    protected function createIndex($indexName, $settings, array $models = [])
    {
        $mappings = null;
        if (! empty($models)) {
            $mappings = $this->getPropertyMappings($models);
        }

        $this->indexManager->createIndex($indexName, $settings, $mappings);
    }

    /**
     * Get the property mappings for the models.
     *
     * @param  array $models
     * @return array
     */
    protected function getPropertyMappings(array $models = [])
    {
        $mappings = [];

        // Add the default property mappings for the model classes.
        foreach ($models as $class) {
            $model = new $class;
            $mappings[$model->getIndexTypeName()] = [
                '_source' => ['enabled' => true],
                'properties' => $model->getIndexMappingProperties()
            ];
        }

        return $mappings;
    }
}
