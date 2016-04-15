<?php

namespace Elodex;

class IndexRepositoryManager
{
    /**
     * Index name used for the index repositories.
     *
     * @var string
     */
    protected $indexName;

    /**
     * The Elasticsearch client instance.
     *
     * @var mixed
     */
    protected $client;

    /**
     * The array of created repositories.
     *
     * @var array
     */
    protected $repositories = [];

    /**
     * Create a new IndexRepository manager instance.
     *
     * @param mixed $client
     * @param string $indexName
     */
    public function __construct($client, $indexName)
    {
        $this->client = $client;
        $this->indexName = $indexName;
    }

    /**
     * Attempt to get the index repository from the local cache.
     *
     * @param  string  $class
     * @return \Elodex\IndexRepository
     */
    public function repository($class)
    {
        return isset($this->repositories[$class])
                    ? $this->repositories[$class]
                    : $this->repositories[$class] = $this->createIndexRepository($class);
    }

    /**
     * Create a new index repository for the model class.
     *
     * @param  string  $class
     * @return \Elodex\IndexRepository
     */
    protected function createIndexRepository($class)
    {
        return new IndexRepository($this->client, $class, $this->indexName);
    }
}
