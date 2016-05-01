<?php

namespace Elodex;

use Elodex\Contracts\IndexClientResolver as IndexClientResolverContract;
use Illuminate\Support\Arr;

class IndexManager implements IndexClientResolverContract
{
    /**
     * The Elodex configuration used for the index manager.
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
     * Create a new index manager instance.
     *
     * @param mixed $client
     * @param array $config
     */
    public function __construct($client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Open the index.
     *
     * @param  string|null $index
     * @return array
     */
    public function openIndex($index = null)
    {
        $indexName = $index ?: $this->getDefaultIndex();
        $params = ['index' => $indexName];

        return $this->client->indices()->open($params);
    }

    /**
     * Close the index.
     *
     * @param  string|null $index
     * @return array
     */
    public function closeIndex($index = null)
    {
        $indexName = $index ?: $this->getDefaultIndex();
        $params = ['index' => $indexName];

        return $this->client->indices()->close($params);
    }

    /**
     * Create a new search index.
     *
     * @param  string|null $index
     * @param  array $settings
     * @param  array|null $mappings
     * @return array
     */
    public function createIndex($index = null, array $settings = [], array $mappings = null)
    {
        $indexName = $index ?: $this->getDefaultIndex();
        $params = ['index' => $indexName];

        // Add global analyzers to the settings.
        $analyzer = $this->getGlobalAnalyzers();
        if (! empty($analyzer)) {
            $settings = array_merge($settings, [
                'analysis' => ['analyzer' => $analyzer],
            ]);
        }

        if (! empty($settings)) {
            Arr::set($params, 'body.settings', $settings);
        }

        if (! empty($mappings)) {
            Arr::set($params, 'body.mappings', $mappings);
        }

        return $this->client->indices()->create($params);
    }

    /**
     * Delete a search index.
     *
     * @param  string|null $index
     * @return array
     */
    public function deleteIndex($index = null)
    {
        $indexName = $index ?: $this->getDefaultIndex();
        $params = ['index' => $indexName];

        return $this->client->indices()->delete($params);
    }

    /**
     * Change the settings for an index.
     *
     * @param  array $settings
     * @param  string|null $index
     * @return array
     */
    public function putSettings(array $settings, $index = null)
    {
        $indexName = $index ?: $this->getDefaultIndex();
        $params = [
            'index' => $indexName,
            'body' => [
                'settings' => $settings,
            ],
        ];

        return $this->client->indices()->putSettings($params);
    }

    /**
     * Get configured settings for an index.
     *
     * @param  string|null $index
     * @return array
     */
    public function getSettings($index = null)
    {
        $indexName = $index ?: $this->getDefaultIndex();
        $params = ['index' => $indexName];

        return $this->client->indices()->getSettings($params);
    }

    /**
     * Put property mappings for a type in the index.
     *
     * @param  string $index
     * @param  string $type
     * @param  array $properties
     * @return array
     */
    public function putMappings($index, $type, array $properties)
    {
        $params = [
            'index' => $index,
            'type' => $type,
            'body' => [
                $type => [
                    '_source' => ['enabled' => true],
                    'properties' => $properties,
                ],
            ],
        ];

        return $this->client->indices()->putMapping($params);
    }

    /**
     * Get the property mappings in the index.
     *
     * @param  string|array $index
     * @param  string|null $type
     * @return array
     */
    public function getMappings($index, $type = null)
    {
        $params = ['index' => $index];

        if (! is_null($type)) {
            $params['type'] = $type;
        }

        return $this->client->indices()->getMapping($params);
    }

    /**
     * Check if the specified indices exist.
     *
     * @param  array $indices
     * @return bool
     */
    public function indicesExist(array $indices)
    {
        $params = ['index' => implode(',', $indices)];

        return $this->client->indices()->exists($params);
    }

    /**
     * Check if the specified types exist in the index.
     *
     * @param  string $index
     * @param  array $types
     * @return bool
     */
    public function typesExist($index, array $types)
    {
        $params = [
            'index' => $index,
            'type' => implode(',', $types),
        ];

        return $this->client->indices()->existsType($params);
    }

    /**
     * Performs the analysis process on a text and return the tokens breakdown of the text.
     *
     * @param  string $analyzer
     * @param  mixed $text
     * @param  array $filters
     * @param  type $tokenizer
     * @param  string|null $index
     * @return array
     */
    public function analyze($analyzer, $text, array $filters = [], $tokenizer = null, $index = null)
    {
        $params = [
            'analyzer' => $analyzer,
            'text' => $text,
        ];

        if (! empty($filters)) {
            $params['filters'] = implode(',', $filters);
        }

        if (! is_null($tokenizer)) {
            $params['tokenizer'] = $tokenizer;
        }

        // Check for a index specific operation.
        if (! is_null($index)) {
            $params['index'] = $index;
        }

        return $this->client->indices()->analyze($params);
    }

    /**
     * Get stats from the index.
     *
     * @param  string|null $index
     * @param  string|null $fields
     * @return array
     */
    public function stats($index = null, $fields = null)
    {
        $params = [];

        if (! is_null($index)) {
            $params['index'] = $index;
        }

        if (! is_null($fields)) {
            $params['fields'] = $fields;
        }

        return $this->client->indices()->stats($params);
    }

    /**
     * Trigger the indices upgrade process.
     *
     * @param  mixed $index
     * @param  bool $wait
     * @return array
     */
    public function upgrade($index = '', $wait = false)
    {
        $params = [
            'index' => $index,
            'wait_for_completion' => $wait,
        ];

        return $this->client->indices()->upgrade($params);
    }

    /**
     * Run a suggestion request.
     *
     * @param  \Elodex\Suggest $suggest
     * @param  string|null $index
     * @return \Elodex\SuggestResult
     */
    public function suggest(Suggest $suggest, $index = null)
    {
        $indexName = $index ?: $this->getDefaultIndex();

        $params = ['index' => $indexName];
        $params['body'] = $suggest->toArray();

        // Perform the suggest request.
        $results = $this->client->suggest($params);

        return new SuggestResult($results);
    }

    /**
     * Return the underlying client instance used for all queries.
     *
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Returns the default index name.
     *
     * @return string
     */
    public function getDefaultIndex()
    {
        return Arr::get($this->config, 'default_index', 'default');
    }

    /**
     * Get the global Elasticsearch analyzers.
     *
     * @return array|null
     */
    public function getGlobalAnalyzers()
    {
        return Arr::get($this->config, 'analyzer', null);
    }
}
