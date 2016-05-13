<?php

namespace Elodex;

class IndexRepositoryManager
{
    /**
     * Default index name used for the index repositories.
     *
     * @var string
     */
    protected $defaultIndexName;

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
     * @param string $defaultIndexName
     */
    public function __construct($client, $defaultIndexName)
    {
        $this->client = $client;
        $this->defaultIndexName = $defaultIndexName;
    }

    /**
     * Attempt to get the index repository from the local cache.
     *
     * @param  string  $class
     * @param  string|null  $index
     * @return \Elodex\IndexRepository
     */
    public function repository($class, $index = null)
    {
        $index = $index ?: $this->defaultIndexName;
        $repositoryId = "{$index}::{$class}";

        return isset($this->repositories[$repositoryId])
                    ? $this->repositories[$repositoryId]
                    : $this->repositories[$repositoryId] = $this->createIndexRepository($class, $index);
    }

    /**
     * Create a new index repository for the model class.
     *
     * @param  string  $class
     * @param  string  $index
     * @return \Elodex\IndexRepository
     */
    protected function createIndexRepository($class, $index)
    {
        return new IndexRepository($this->client, $class, $index);
    }
}
