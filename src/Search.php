<?php

namespace Elodex;

use ONGR\ElasticsearchDSL\Search as SearchDSL;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Query\BoolQuery;
use ONGR\ElasticsearchDSL\Query\TermQuery;
use ONGR\ElasticsearchDSL\Query\TermsQuery;
use ONGR\ElasticsearchDSL\Query\CommonTermsQuery;
use ONGR\ElasticsearchDSL\Query\PrefixQuery;
use ONGR\ElasticsearchDSL\Query\MatchQuery;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Query\MultiMatchQuery;
use ONGR\ElasticsearchDSL\Query\RegexpQuery;
use ONGR\ElasticsearchDSL\Query\WildcardQuery;
use ONGR\ElasticsearchDSL\Query\FuzzyQuery;
use ONGR\ElasticsearchDSL\Query\QueryStringQuery;
use ONGR\ElasticsearchDSL\Query\NestedQuery;
use ONGR\ElasticsearchDSL\Highlight\Highlight;
use ONGR\ElasticsearchDSL\Suggest\TermSuggest;
use ONGR\ElasticsearchDSL\BuilderInterface;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Str;
use Elodex\Pagination\LengthAwarePaginator;
use InvalidArgumentException;
use BadMethodCallException;

class Search
{
    /**
     * The indexed model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The search DSL instance.
     *
     * @var \ONGR\ElasticsearchDSL\Search
     */
    protected $search;

    /**
     * Blacklisted methods that should not be directly called on the query builder.
     *
     * @var array
     */
    protected $blacklist = [
    ];

    /**
     * The methods that should be directly returned from search DSL query builder.
     *
     * @var array
     */
    protected $passthrough = [
        'toArray', 'isExplain',
    ];

    /**
     * Create a new search instance.
     */
    public function __construct()
    {
        $this->search = new SearchDSL();
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setModel(EloquentModel $model)
    {
        if (! method_exists($model, 'getIndexRepository')) {
            throw new InvalidArgumentException('Invalid model for index search');
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Get the results for the search on the index repository of the model.
     *
     * @return \Elodex\SearchResult
     */
    public function get()
    {
        return $this->getModel()->getIndexRepository()->search($this);
    }

    /**
     * Count the results for the search on the index repository of the model.
     *
     * @return \Elodex\SearchResult
     */
    public function count()
    {
        return $this->getModel()->getIndexRepository()->count($this);
    }

    /**
     * Add a term query to the search.
     *
     * @param  string $field
     * @param  string $value
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function term($field, $value, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $termQuery = new TermQuery($field, $value, $parameters);

        $this->addQuery($termQuery, $boolType);

        return $this;
    }

    /**
     * Add a terms query to the search.
     *
     * @param  string $field
     * @param  array $terms
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function terms($field, $terms, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $termsQuery = new TermsQuery($field, $terms, $parameters);

        $this->addQuery($termsQuery, $boolType);

        return $this;
    }

    /**
     * Add a common terms query to the search.
     *
     * @param  string $field
     * @param  string $query
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function commonTerms($field, $query, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $commonTermsQuery = new CommonTermsQuery($field, $query, $parameters);

        $this->addQuery($commonTermsQuery, $boolType);

        return $this;
    }

    /**
     * Add a prefix query to the search.
     *
     * @param  string $field
     * @param  string $value
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function prefix($field, $value, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $prefix = new PrefixQuery($field, $value, $parameters);

        $this->addQuery($prefix, $boolType);

        return $this;
    }

    /**
     * Add a match query to the search.
     *
     * @param  string $field
     * @param  string $query
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function match($field, $query, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $matchQuery = new MatchQuery($field, $query, $parameters);

        $this->addQuery($matchQuery, $boolType);

        return $this;
    }

    /**
     * Add a match all query to the search.
     *
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function matchAll($boolType = BoolQuery::MUST, array $parameters = [])
    {
        $matchAllQuery = new MatchAllQuery($parameters);

        $this->addQuery($matchAllQuery, $boolType);

        return $this;
    }

    /**
     * Add a multi match query to the search.
     *
     * @param  array $fields
     * @param  string $query
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function multiMatch(array $fields, $query, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $multiMatchQuery = new MultiMatchQuery($fields, $query, $parameters);

        $this->addQuery($multiMatchQuery, $boolType);

        return $this;
    }

    /**
     * Add a regexp query to the search.
     *
     * @param  string $field
     * @param  string $regexpValue
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function regexp($field, $regexpValue, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $regexpQuery = new RegexpQuery($field, $regexpValue, $parameters);

        $this->addQuery($regexpQuery, $boolType);

        return $this;
    }

    /**
     * Add a wildcard query to the search.
     *
     * @param  string $field
     * @param  string $value
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function wildcard($field, $value, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $wildcardQuery = WildcardQuery($field, $value, $parameters);

        $this->addQuery($wildcardQuery, $boolType);

        return $this;
    }

    /**
     * Add a fuzzy query to the search.
     *
     * @param  string $field
     * @param  string $value
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function fuzzy($field, $value, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $fuzzyQuery = new FuzzyQuery($field, $value, $parameters);

        $this->addQuery($fuzzyQuery, $boolType);

        return $this;
    }

    /**
     * Add a query string query to the search.
     *
     * @param  string $query
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function queryString($query, $boolType = BoolQuery::MUST, array $parameters = [])
    {
        $queryStringQuery = new QueryStringQuery($query, $parameters);

        $this->addQuery($queryStringQuery, $boolType);

        return $this;
    }

    /**
     * Add a nested query to the search.
     *
     * @param  string $path
     * @param  \ONGR\ElasticsearchDSL\BuilderInterface $query
     * @param  string $boolType
     * @param  array $parameters
     * @return $this
     */
    public function nestedQuery($path, BuilderInterface $query, $boolType = BoolQuery::MUST, $parameters = [])
    {
        $nestedQuery = new NestedQuery($path, $query, $parameters);

        $this->addQuery($nestedQuery, $boolType);

        return $this;
    }

    /**
     * Set maximum number of results.
     *
     * @param  int $value
     * @return $this
     */
    public function limit($value)
    {
        $this->setSize($value);

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the "offset" for the query.
     *
     * @param  int $value
     * @return $this
     */
    public function offset($value)
    {
        $this->setFrom($value);

        return $this;
    }

    /**
     * Add a field sort to the search.
     *
     * @param  string $field
     * @param  string $order
     * @param  string $params
     * @return $this
     */
    public function sort($field, $order = null, $params = [])
    {
        $sort = new FieldSort($field, $order, $params);

        $this->addSort($sort);

        return $this;
    }

    /**
     * Alias for sort method.
     *
     * @param  string $field
     * @param  string $order
     * @param  string $params
     * @return $this
     */
    public function orderBy($field, $order = null, $params = [])
    {
        return $this->sort($field, $order, $params);
    }

    /**
     * Add a highlighted field to the search.
     *
     * @param  string $field
     * @param  array $params
     * @return $this
     */
    public function highlight($field, array $params = [])
    {
        $highlight = $this->getHighlight();

        if (is_null($highlight)) {
            $highlight = new Highlight();

            $this->addHighlight($highlight);
        }

        $highlight->addField($field, $params);

        return $this;
    }

    /**
     * Sets html tag and its class used in highlighting.
     *
     * @param  array $preTags
     * @param  array $postTags
     * @return $this
     */
    public function withHighlightTags(array $preTags, array $postTags)
    {
        $highlight = $this->getHighlight();

        if (is_null($highlight)) {
            $highlight = new Highlight();

            $this->addHighlight($highlight);
        }

        $highlight->setTags($preTags, $postTags);

        return $this;
    }

    /**
     * Add a term suggest request to the search query.
     *
     * @param  string $name
     * @param  string $text
     * @param  array $parameters
     * @return $this
     */
    public function suggestTerm($name, $text, array $parameters = [])
    {
        $suggest = new TermSuggest($name, $text, $parameters);

        $this->addSuggest($suggest);

        return $this;
    }

    /**
     * Paginate the search.
     *
     * @param  int|null $perPage
     * @param  string $pageName
     * @param  int|null $page
     * @return $this
     */
    public function paginate($perPage = null, $pageName = 'page', $page = null)
    {
        $perPage = $perPage ?: $this->getModel()->getPerPage();
        $page = $page ?: (int) Paginator::resolveCurrentPage($pageName);

        $this->search->setFrom($perPage * $page);
        $this->search->setSize($perPage);

        $results = $this->get();

        return new LengthAwarePaginator($results, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Make a scrolling request for the search expecting a large number of results.
     *
     * @param  string $duration
     * @param  callable $callback
     * @return void
     */
    public function scroll($duration, callable $callback)
    {
        $this->setScroll($duration);

        $this->getModel()->getIndexRepository()->scroll($this, $callback);
    }

    /**
     * Return the search query DSL builder.
     *
     * @return \ONGR\ElasticsearchDSL\Search
     */
    public function getDSL()
    {
        return $this->search;
    }

    /**
     * Handle dynamic method calls into the search.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        // Check the blacklist for the method.
        if (in_array($method, $this->blacklist)) {
            $className = static::class;

            throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
        }

        // Explicit passthroughs to the search query instance.
        if (in_array($method, $this->passthrough)) {
            return call_user_func_array([$this->search, $method], $parameters);
        }

        // Getters should return their result instead of $this.
        if (Str::startsWith($method, 'get')) {
            return call_user_func_array([$this->search, $method], $parameters);
        }

        // Proxied call to the underlying search instance.
        call_user_func_array([$this->search, $method], $parameters);

        return $this;
    }
}
