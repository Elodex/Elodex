<?php

namespace Elodex\Facades;

use Illuminate\Support\Facades\Facade;

class Index extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'elodex.index';
    }
}
