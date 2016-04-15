<?php

namespace Functional;

use Elodex\Model as ElodexModel;
use Mockery as m;

abstract class MappingModel extends ElodexModel
{
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $indexMappingDateFormat = 'yyyy-MM-dd HH:mm:ss';

    public function setDates($dates)
    {
        $this->dates = $dates;
        return $this;
    }

    public function setCasts($casts)
    {
        $this->casts = $casts;
        return $this;
    }

    public function setIndexRelations($indexRelations)
    {
        $this->indexRelations = $indexRelations;
        return $this;
    }

    public function getCustomIndexMappingProperties()
    {
        return $this->indexMappingProperties;
    }

    public function setCustomIndexMappingProperties($indexMappingProperties)
    {
        $this->indexMappingProperties = $indexMappingProperties;
        return $this;
    }

    public function getConnection()
    {
        $mock = m::mock(\Illuminate\Database\Connection::class);
        return $mock;
    }
}
