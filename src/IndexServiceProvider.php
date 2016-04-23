<?php

namespace Elodex;

use Illuminate\Support\ServiceProvider;
use Elodex\Console\OpenIndex;
use Elodex\Console\CloseIndex;
use Elodex\Console\DeleteIndex;
use Elodex\Console\CreateIndex;
use Elodex\Console\GetMappings;
use Elodex\Console\GetStats;
use Elodex\Console\Upgrade;
use Elodex\Console\Analyze;

class IndexServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->setupConfig();

        $this->registerElasticsearchClient();
        $this->registerIndexManager();
        $this->registerIndexRepositoryManager();

        $this->registerCommands();
    }

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__.'/config/elodex.php');

        $this->publishes([$source => config_path('elodex.php')], 'config');

        $this->mergeConfigFrom($source, 'elodex');
    }

    /**
     * Register the Elasticsearch client manager.
     *
     * @return void
     */
    protected function registerElasticsearchClient()
    {
        $this->app->singleton(ElasticsearchClientManager::class, function ($app) {
            $config = $app['config']->get('elodex.config');

            return new ElasticsearchClientManager($config);
        });

        $this->app->alias(ElasticsearchClientManager::class, 'elodex.client');
    }

    /**
     * Register the index manager.
     *
     * @return void
     */
    protected function registerIndexManager()
    {
        $this->app->singleton(IndexManager::class, function ($app) {
            $client = $app[ElasticsearchClientManager::class];
            $defaultIndex = $app['config']->get('elodex.default_index', 'default');

            return new IndexManager($client, $defaultIndex);
        });

        $this->app->alias(IndexManager::class, 'elodex.index');
    }

    /**
     * Register the index repostiory manager.
     *
     * @return void
     */
    protected function registerIndexRepositoryManager()
    {
        $this->app->singleton(IndexRepositoryManager::class, function ($app) {
            $client = $app[ElasticsearchClientManager::class];
            $indexName = $app['config']->get('elodex.default_index', 'default');

            return new IndexRepositoryManager($client, $indexName);
        });

        $this->app->alias(IndexRepositoryManager::class, 'elodex.repository');
    }

    /**
     * Register the console commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $indexManager = $this->app[IndexManager::class];

        $this->app->singleton(OpenIndex::class, function () use ($indexManager) {
            return new OpenIndex($indexManager);
        });
        $this->app->singleton(CloseIndex::class, function () use ($indexManager) {
            return new CloseIndex($indexManager);
        });
        $this->app->singleton(CreateIndex::class, function () use ($indexManager) {
            return new CreateIndex($indexManager);
        });
        $this->app->singleton(DeleteIndex::class, function () use ($indexManager) {
            return new DeleteIndex($indexManager);
        });
        $this->app->singleton(GetMappings::class, function () use ($indexManager) {
            return new GetMappings($indexManager);
        });
        $this->app->singleton(GetStats::class, function () use ($indexManager) {
            return new GetStats($indexManager);
        });
        $this->app->singleton(Upgrade::class, function () use ($indexManager) {
            return new Upgrade($indexManager);
        });
        $this->app->singleton(Analyze::class, function () use ($indexManager) {
            return new Analyze($indexManager);
        });

        $this->commands(
            OpenIndex::class,
            CloseIndex::class,
            CreateIndex::class,
            DeleteIndex::class,
            GetMappings::class,
            GetStats::class,
            Upgrade::class,
            Analyze::class
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'elodex.client', ElasticsearchClientManager::class,
            'elodex.index', IndexManager::class,
            'elodex.repository', IndexRepositoryManager::class,
        ];
    }
}
