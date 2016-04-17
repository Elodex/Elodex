<?php

namespace Elodex\Contracts;

use Elodex\Search;

interface IndexRepositoryScrolling
{
    /**
     * Search using a scroll request for a large number of results.
     *
     * @param  \Elodex\Search $search
     * @param  callable $callback
     * @return void
     */
    public function scroll(Search $search, callable $callback);
}
