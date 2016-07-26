<?php

namespace Elodex;

use Elodex\Search;
use Illuminate\Container\Container;

trait IndexRepositoryAccess
{
    /**
     * Return the index repository used for this model instance.
     *
     * @return \Elodex\IndexRepository
     */
    public function getIndexRepository()
    {
        $app = Container::getInstance();
        return $app->make('elodex.repository', [])->repository(get_class($this));
    }

    /**
     * Add the model's document to the index.
     *
     * @return array
     */
    public function addToIndex()
    {
        return $this->getIndexRepository()->add($this);
    }

    /**
     * Update the index for the model's document.
     *
     * @return array
     */
    public function updateIndex()
    {
        return $this->getIndexRepository()->update($this);
    }

    /**
     * Remove the model's document from the index.
     *
     * @return array
     */
    public function removeFromIndex()
    {
        return $this->getIndexRepository()->remove($this);
    }

    /**
     * Add or replace the model's document to the index.
     *
     * @return array
     */
    public function saveToIndex()
    {
        return $this->getIndexRepository()->save($this);
    }

    /**
     * Add or replace the model's documents to the index.
     *
     * @return array
     */
    public function saveBulkToIndex()
    {
        return $this->getIndexRepository()->saveCollection($this);
    }

    /**
     * Create a new index search query.
     *
     * @return \Elodex\Search
     */
    public function newIndexSearch()
    {
        $search = new Search();

        $search->setModel($this);

        return $search;
    }

    /**
     * Return the index repository used for this model class.
     *
     * @return \Elodex\IndexRepository
     */
    public static function getClassIndexRepository()
    {
        $app = Container::getInstance();
        return $app->make('elodex.repository', [])->repository(get_called_class());
    }

    /**
     * Create a new index based search query.
     *
     * @return \Elodex\Search
     */
    public static function indexSearch()
    {
        return (new static)->newIndexSearch();
    }
}
