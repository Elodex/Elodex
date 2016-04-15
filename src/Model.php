<?php

namespace Elodex;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Elodex\Contracts\IndexedModel as IndexedModelContract;
use Elodex\IndexedModel;

abstract class Model extends BaseModel implements IndexedModelContract
{
    use IndexedModel;
}
