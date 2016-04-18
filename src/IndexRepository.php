<?php

namespace Elodex;

use Elodex\Contracts\IndexedModel as IndexedModelContract;
use Elodex\Contracts\IndexRepository as IndexRepositoryContract;
use Elodex\Contracts\IndexRepositoryScrolling as IndexRepositoryScrollingContract;
use Elodex\Search;
use Elodex\SearchResult;
use Elodex\Exceptions\InvalidArgumentException;
use Elodex\Exceptions\BulkOperationException;
use Elodex\Exceptions\MultiGetException;
use Illuminate\Support\Collection as BaseCollection;

class IndexRepository implements IndexRepositoryContract, IndexRepositoryScrollingContract
{
    /**
     * The client instance used for all requests.
     *
     * @var mixed
     */
    protected $client;

    /**
     * The name of the index used for the repository.
     *
     * @var string
     */
    protected $indexName;

    /**
     * The index type name used for all queries.
     *
     * @var string
     */
    protected $indexTypeName;

    /**
     * The indexed model class used for this repository.
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Indicator if shards should be immediately refreshed after certain write
     * operations.
     *
     * https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-index_.html#index-refresh
     *
     * @var bool
     */
    protected $shouldRefreshShard = false;

    /**
     * Create a new index repository instance.
     *
     * @param  mixed $client
     * @param  string $modelClass
     * @param  string $indexName
     * @throws \Elodex\Exceptions\InvalidArgumentException
     */
    public function __construct($client, $modelClass, $indexName)
    {
        $model = new $modelClass;
        if (! $model instanceof IndexedModelContract) {
            throw new InvalidArgumentException('Model class does not implement the IndexedModel interface');
        }

        $this->client = $client;
        $this->modelClass = $modelClass;
        $this->indexName = $indexName;
        $this->indexTypeName = $model->getIndexTypeName();
    }

    /**
     * {@inheritdoc}
     * @throws \Elodex\Exceptions\InvalidArgumentException
     */
    public function add($model)
    {
        if ($model instanceof BaseCollection) {
            return $this->addCollection($model);
        }

        $this->validateModelClass($model);

        if (! $model->canAddToIndex()) {
            throw new InvalidArgumentException('Model instance cannot be added to the index repository.');
        }

        $params = $this->getEntityBaseParams($model);
        $this->addGenericWriteParams($params);

        $params['body'] = $model->toIndexDocument();

        return $this->client->create($params);
    }

    /**
     * Add a collection of model entity index documents.
     *
     * @param  \Illuminate\Support\Collection $collection
     * @return array
     */
    public function addCollection(BaseCollection $collection)
    {
        if ($collection->isEmpty()) {
            return [];
        }

        $this->validateModelClass($collection->first());

        $params = [];
        $this->addGenericWriteParams($params);

        foreach ($collection as $item) {
            $params['body'][] = ['create' => $this->getEntityBulkParams($item)];
            $params['body'][] = $item->toIndexDocument();
        }

        $results = $this->client->bulk($params);
        $this->checkBulkResults($results, $collection);

        return $results;
    }

    /**
     * {@inheritdoc}
     * @throws \Elodex\Exceptions\InvalidArgumentException
     */
    public function update($model)
    {
        if ($model instanceof BaseCollection) {
            return $this->updateCollection($model);
        }

        $this->validateModelClass($model);

        if (! $model->canAddToIndex()) {
            throw new InvalidArgumentException('Model instance cannot be added to the index repository.');
        }

        $params = $this->getEntityBaseParams($model);
        $this->addGenericWriteParams($params);

        $doc = $model->getChangedIndexDocument();
        if (empty($doc)) {
            throw new InvalidArgumentException("The document of the model does not contain any changes and thus can't be updated.");
        }
        $params['body']['doc'] = $doc;

        return $this->client->update($params);
    }

    /**
     * Update a collection of model entity index documents.
     *
     * @param  \Illuminate\Support\Collection $collection
     * @return array
     */
    public function updateCollection(BaseCollection $collection)
    {
        if ($collection->isEmpty()) {
            return [];
        }

        $this->validateModelClass($collection->first());

        $params = [];
        $this->addGenericWriteParams($params);

        foreach ($collection->all() as $item) {
            $doc = $item->getChangedIndexDocument();

            if (! empty($doc)) {
                $params['body'][] = ['update' => $this->getEntityBulkParams($item)];
                $params['body'][] = ['doc' => $doc];
            }
        }

        $results = $this->client->bulk($params);
        $this->checkBulkResults($results, $collection);

        return $results;
    }

    /**
     * {@inheritdoc}
     * @throws \Elodex\Exceptions\InvalidArgumentException
     */
    public function save($model)
    {
        if ($model instanceof BaseCollection) {
            return $this->saveCollection($model);
        }

        $this->validateModelClass($model);

        if (! $model->canAddToIndex()) {
            throw new InvalidArgumentException('Model instance cannot be added to the index repository.');
        }

        $params = $this->getEntityBaseParams($model);
        $this->addGenericWriteParams($params);

        $params['body'] = $model->toIndexDocument();

        return $this->client->index($params);
    }

    /**
     * Add or replace a collection of model entity index documents.
     *
     * @param  \Illuminate\Support\Collection $collection
     * @return array
     */
    public function saveCollection(BaseCollection $collection)
    {
        if ($collection->isEmpty()) {
            return [];
        }

        $this->validateModelClass($collection->first());

        $params = [];
        $this->addGenericWriteParams($params);

        foreach ($collection->all() as $item) {
            $params['body'][] = ['index' => $this->getEntityBulkParams($item)];
            $params['body'][] = $item->toIndexDocument();
        }

        $results = $this->client->bulk($params);
        $this->checkBulkResults($results, $collection);

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($model)
    {
        if ($model instanceof BaseCollection) {
            return $this->removeCollection($model);
        }

        $this->validateModelClass($model);

        $params = $this->getEntityBaseParams($model);
        $this->addGenericWriteParams($params);

        return $this->client->delete($params);
    }

    /**
     * Remove all documents for the specified collection of model entities.
     *
     * @param  \Illuminate\Support\Collection $collection
     * @return array
     */
    public function removeCollection(BaseCollection $collection)
    {
        if ($collection->isEmpty()) {
            return [];
        }

        $this->validateModelClass($collection->first());

        $all = $collection->all();

        $params = [];
        $this->addGenericWriteParams($params);

        foreach ($all as $item) {
            $params['body'][] = [
                'delete' => $this->getEntityBulkParams($item),
            ];
        }

        // Note that Elasticsearch won't report any error if a document cannot
        // be found. It will just mark the entry with ["found"=>"false"] and
        // the status code of the item will be 404.
        $results = $this->client->bulk($params);
        $this->checkBulkResults($results, $collection);

        return $results;
    }

    /**
     * Get the indexed document for the specified entity.
     *
     * @param  \Elodex\Contracts\IndexedModel|\Illuminate\Support\Collection $model
     * @return array
     */
    public function getDocument($model, $fields = null)
    {
        $this->validateModelClass($model);

        $params = $this->getEntityBaseParams($model);

        if (! is_null($fields)) {
            $params['_source'] = $fields;
        }

        return $this->client->get($params);
    }

    /**
     * Get the documents for the specified collection.
     *
     * @param  \Illuminate\Support\Collection $collection
     * @return array
     */
    public function getDocuments(BaseCollection $collection, $fields = null)
    {
        if ($collection->isEmpty()) {
            return [];
        }

        $this->validateModelClass($collection->first());

        $all = $collection->all();

        // Create a dictionary for model collection.
        $modelIds = array_map(function ($m) {
            return $m->getIndexKey();
        }, $all);

        $params = $this->getBaseParams();
        $params['body']['ids'] = $modelIds;

        if (! is_null($fields)) {
            $params['_source'] = $fields;
        }

        $results = $this->client->mget($params);

        $this->checkMultiGetResults($results, $collection);

        return $results['docs'];
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $with = null, $limit = null, $offset = null)
    {
        $search = new Search();

        if (! is_null($limit)) {
            $search->setSize($limit);
        }

        if (! is_null($offset)) {
            $search->setFrom($offset);
        }

        $results = $this->search($search);

        return $results->getItems($with);
    }

    /**
     * Search all fields of all indexed models for the specified term.
     *
     * @param  string $term
     * @param  array|null $aggregations
     * @param  array|null $sourceFields
     * @param  int|null   $limit
     * @param  int|null   $offset
     * @return \Elodex\SearchResult
     */
    public function searchAllFields($term, $limit = null, $offset = null)
    {
        $search = new Search();
        $search->setSize($limit);
        $search->setFrom($offset);

        $search->match('_all', $term);

        return $this->search($search);
    }

    /**
     * {@inheritdoc}
     */
    public function search(Search $search)
    {
        // Build the basic query parameters.
        $params = $search->getQueryParams();
        $params = array_merge($params, $this->getBaseParams());

        // Version info should be included in the result by default.
        $params['version'] = true;

        // Generate the body out of the search query.
        $params['body'] = $search->toArray();

        // Perform the search request.
        $results = $this->client->search($params);

        return new SearchResult($results, $this->modelClass);
    }

    /**
     * {@inheritdoc}
     */
    public function count(Search $search)
    {
        $params = $search->getQueryParams();
        $params = array_merge($params, $this->getBaseParams());

        $params['body'] = $search->toArray();

        $results = $this->client->count($params);

        return $results['count'];
    }

    /**
     * {@inheritdoc}
     * @throws \Elodex\Exceptions\InvalidArgumentException
     */
    public function scroll(Search $search, callable $callback)
    {
        $duration = $search->getScroll();

        if (is_null($duration)) {
            throw new InvalidArgumentException('Scroll duration missing on search query.');
        }

        // Get the first result for the scrolling request.
        $result = $this->search($search);
        $scrollId = $result->getScrollId();

        try {
            // Callback for the first set of results.
            call_user_func($callback, $result);

            // Check if we do have more documents than we got from the first call.
            if (count($result) < $result->totalHits()) {
                // Scroll through the results until we don't get any more hits.
                while (true) {
                    $result = $this->scrollRequest($scrollId, $duration);
                    $scrollId = $result->getScrollId();

                    // Check if we didn't get any more results.
                    if (count($result) === 0) {
                        break;
                    }

                    call_user_func($callback, $result);
                }
            }
        } finally {
            $this->clearScroll($scrollId);
        }
    }

    /**
     * Send a scrolling request for the given scrolling ID.
     *
     * @param  string $scrollId
     * @param  string $duration
     * @return \Elodex\SearchResult
     */
    protected function scrollRequest($scrollId, $duration)
    {
        $params = [
            'scroll_id' => $scrollId,
            'scroll' => $duration,
        ];

        $results = $this->client->scroll($params);

        return new SearchResult($results, $this->modelClass);
    }

    /**
     * Clear the search context of a scrolling search.
     *
     * @param  string $scrollId
     * @return array
     */
    protected function clearScroll($scrollId)
    {
        $params = [ 'scroll_id' => $scrollId ];

        $results = $this->client->clearScroll($params);

        return $results;
    }

    /**
     * Returns the value of the shard refresh option.
     *
     * @return bool
     */
    public function getShouldRefreshShard()
    {
        return $this->shouldRefreshShard;
    }

    /**
     * Set the value of the shard refresh option.
     *
     * @param  bool $value
     * @return \Elodex\IndexRepository
     */
    public function setShouldRefreshShard($value)
    {
        $this->shouldRefreshShard = $value;

        return $this;
    }

    /**
     * Validates the model's class and makes sure it's class is compatible with the
     * index repository.
     *
     * @param  mixed $model
     * @throws \Elodex\Exceptions\InvalidArgumentException
     */
    protected function validateModelClass($model)
    {
        if (get_class($model) !== $this->modelClass) {
            throw new InvalidArgumentException("Index repository does only accept '{$this->modelClass}' instances.");
        }
    }

    /**
     * Check the results returned by a bulk operation.
     *
     * @param  array $results
     * @param  \Illuminate\Support\Collection $collection
     * @return void
     * @throws \Elodex\Exceptions\BulkOperationException
     */
    protected function checkBulkResults(array $results, BaseCollection $collection)
    {
        if (isset($results['errors']) && ($results['errors'] === true)) {
            throw BulkOperationException::createForResults($results['items'], $collection->all());
        }
    }

    /**
     * Checks the results of a multi GET for errors.
     *
     * @param  array $results
     * @param  \Illuminate\Support\Collection $collection
     * @throws \Elodex\Exceptions\MultiGetException
     */
    protected function checkMultiGetResults(array $results, BaseCollection $collection)
    {
        $modelDictionary = $collection->keyBy('id')->all();

        $failedItems = [];
        $errors = [];

        foreach ($results['docs'] as $doc) {
            $documentId = $doc['_id'];

            if (isset($doc['error'])) {
                $failedItems[$documentId] = $modelDictionary[$documentId];
                $errors[$documentId] = $doc['error'];

                continue;
            }

            if ($doc['found'] === false) {
                $failedItems[$documentId] = $modelDictionary[$documentId];
                $errors[$documentId] = ['reason' => 'document not found'];
            }
        }

        if (! empty($failedItems)) {
            throw MultiGetException::createForFailedItems($failedItems, $errors);
        }
    }

    /**
     * Perform a raw search with the specified parameters.
     *
     * @param  array $params
     * @return \Elodex\SearchResult
     */
    protected function rawSearch(array $params)
    {
        $params = array_merge($params, $this->getBaseParams());

        $results = $this->client->search($params);

        return new SearchResult($results, $this->modelClass);
    }

    /**
     * Get the params used for index queries.
     *
     * @return array
     */
    protected function getBaseParams()
    {
        return [
            'index' => $this->indexName,
            'type' => $this->indexTypeName,
        ];
    }

    /**
     * Get the entity base params used for index queries.
     *
     * @param  \Elodex\Contracts\IndexedModel $model
     * @return array
     */
    protected function getEntityBaseParams(IndexedModelContract $model)
    {
        $params = $this->getBaseParams();
        $params['id'] = $model->getIndexKey();
        return $params;
    }

    /**
     * Get the entity params used for bulk indexing queries.
     *
     * https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_indexing_documents.html#_bulk_indexing
     *
     * @param  \Elodex\Contracts\IndexedModel $model
     * @return array
     */
    protected function getEntityBulkParams(IndexedModelContract $model)
    {
        return [
            '_index' => $this->indexName,
            '_type' => $this->indexTypeName,
            '_id' => $model->getIndexKey()
        ];
    }

    /**
     * Add generic write parameters to the specified params array.
     *
     * @param  array $params
     * @return array
     */
    protected function addGenericWriteParams(array &$params)
    {
        if ($this->getShouldRefreshShard()) {
            $params['refresh'] = true;
        }

        return $params;
    }
}
