<?php

namespace Elodex;

use Elodex\Contracts\IndexClientResolver as IndexClientResolverContract;

class ElasticsearchClientManager implements IndexClientResolverContract
{
    /**
     * The client configuration used to create the client instance.
     *
     * @var array
     */
    protected $config;

    /**
     * The Elasticsearch client instance.
     *
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * Create a new Elasticsearch client manager instance.
     *
     * @param  array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->client = $this->createClient($this->getConfig());
    }

    /**
     * Create a new ElasticSearch client instance.
     *
     * @param  array $config
     * @return \Elasticsearch\Client
     */
    protected function createClient($config)
    {
        // Use the Elasticsearch ClientBuilder helper to create the search client
        $config = $this->createLoggerConfig($config);

        return \Elasticsearch\ClientBuilder::fromConfig($config);
    }

    /**
     * Create a new logger instance for the Elasticsearch client and modify the
     * config for the client builder accordingly.
     *
     * @param  array $config
     * @return array
     */
    protected function createLoggerConfig($config)
    {
        $logger = \Elasticsearch\ClientBuilder::defaultLogger($config['logPath'], $config['logLevel']);
        unset($config['logPath'], $config['logLevel']);
        $config['logger'] = $logger;

        return $config;
    }

    /**
     * Get the Elodex configuration.
     *
     * @return array
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * Return the Elasticsearch client instance.
     *
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Dynamically call the search client instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->client, $method], $parameters);
    }
}
