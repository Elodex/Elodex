<?php

namespace Elodex;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use ONGR\ElasticsearchDSL\Suggest\TermSuggest;

class Suggest implements Arrayable
{
    /**
     * The index manager instance used for the requests.
     *
     * @var \Elodex\IndexManager
     */
    protected $indexManager;

    /**
     * Global suggest text to use.
     *
     * @var string
     */
    protected $text;

    /**
     * List of suggesters.
     *
     * @var array
     */
    protected $suggesters = [];

    /**
     * Create a new suggest instance.
     *
     * @param \Elodex\IndexManager $indexManager
     */
    public function __construct(IndexManager $indexManager)
    {
        $this->indexManager = $indexManager;
    }

    /**
     * Set the global suggest text.
     *
     * @param  string $value
     * @return \Elodex\Suggest
     */
    public function setText($value)
    {
        $this->text = $value;

        return $this;
    }

    /**
     * Get the global suggest text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Add a term suggestion request.
     *
     * @param  string $name
     * @param  string $text
     * @param  array $parameters
     * @return $this
     */
    public function term($name, $text, array $parameters = [])
    {
        $this->suggesters[] = new TermSuggest($name, $text, $parameters);

        return $this;
    }

    /**
     * Run the suggestion request.
     *
     * @param  string|null $index
     * @return \Elodex\SuggestResult
     */
    public function get($index = null)
    {
        return $this->indexManager->suggest($this, $index);
    }

    /**
     * Get the suggest query array.
     *
     * @return array
     */
    public function toArray()
    {
        $output = [];

        if (! empty($this->text)) {
            $output['text'] = $this->text;
        }

        foreach ($this->suggesters as $suggester) {
            $output = array_merge($output, $suggester->toArray());
        }

        return array_filter($output);
    }

    /**
     * Create a new suggest query instance.
     *
     * @param  \Elodex\IndexManager|null $indexManager
     * @return \Elodex\Suggest
     */
    public static function create(IndexManager $indexManager = null)
    {
        if (is_null($indexManager)) {
            return Container::getInstance()->make(static::class);
        }

        return new static($indexManager);
    }
}
