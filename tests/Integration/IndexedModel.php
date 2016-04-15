<?php

namespace Integration;

use Elodex\Contracts\IndexedModel as IndexedModelContract;

class IndexedModel implements IndexedModelContract
{
    protected $id;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getIndexTypeName()
    {
        return get_class();
    }

    public function getIndexKey()
    {
        return $this->id;
    }

    public function setIndexVersion($version)
    {
        return $this;
    }

    public function getIndexVersion()
    {
        return null;
    }

    public function setIndexScore($score)
    {
        return $this;
    }

    public function getIndexScore()
    {
        return 1.0;
    }

    public function toIndexDocument()
    {
        return ['foo' => 'bar', 'test' => 1];
    }

    public function getChangedIndexDocument()
    {
        return ['foo' => 'bar'];
    }

    public function canAddToIndex()
    {
        return true;
    }
}


