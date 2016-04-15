<?php

namespace Elodex\Contracts;

use Elodex\Search;

interface IndexRepository
{
    /**
     * Add the model's document representation to the index.
     *
     * @param  \Elodex\Contracts\IndexedModel|\Illuminate\Support\Collection $model
     * @return array
     */
    public function add($model);

    /**
     * Update the indexed document for the model entity.
     *
     * @param  \Elodex\Contracts\IndexedModel|\Illuminate\Support\Collection $model
     * @return array
     */
    public function update($model);

    /**
     * Remove the indexed document for the model entity.
     *
     * @param  \Elodex\Contracts\IndexedModel|\Illuminate\Support\Collection $model
     * @return array
     */
    public function remove($model);

    /**
     * Add or replace the model's document representation in the index.
     *
     * @param  \Elodex\Contracts\IndexedModel|\Illuminate\Support\Collection $model
     * @return array
     */
    public function save($model);

    /**
     * Get all indexed model entities.
     *
     * @param  array|null $with
     * @param  int|null $limit
     * @param  int|null $offset
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all(array $with = null, $limit = null, $offset = null);

    /**
     * Perform a search on the index repository.
     *
     * @param  \Elodex\Search $search
     * @return \Elodex\SearchResult
     */
    public function search(Search $search);

    /**
     * Count the number of documents for a search.
     *
     * @param  \Elodex\Search $search
     * @return int
     */
    public function count(Search $search);
}
