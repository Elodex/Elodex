<?php

namespace Elodex\Pagination;

use Illuminate\Pagination\LengthAwarePaginator as BasePaginator;

class LengthAwarePaginator extends BasePaginator
{
    /**
     * The search result instance for this paginator.
     *
     * @var \Elodex\SearchResult
     */
    protected $searchResult;

    /**
     * Create a new paginator instance.
     *
     * @param  \Elodex\SearchResult $searchResult
     * @param  int $total
     * @param  int $perPage
     * @param  int|null $currentPage
     * @param  array $options (path, query, fragment, pageName)
     */
    public function __construct($searchResult, $total, $perPage, $currentPage = null, array $options = [])
    {
        $this->searchResult = $searchResult;

        $items = $this->searchResult->getDocuments();

        parent::__construct($items, $total, $perPage, $currentPage, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $data = parent::toArray();

        $data['search_result'] = $this->searchResult->toArray();

        return $data;
    }

    /**
     * Get the collection of models for the search result.
     *
     * @param  array|null $with
     * @return \Illuminate\Database\Eloquent\Collection|array
     */
    public function getItems(array $with = null)
    {
        return $this->searchResults->getItems($with);
    }

    /**
     * Get the search result instance for this paginator.
     *
     * @return \Elodex\SearchResult
     */
    public function getSearchResult()
    {
        return $this->searchResult;
    }
}
