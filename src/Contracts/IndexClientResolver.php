<?php

namespace Elodex\Contracts;

interface IndexClientResolver
{
    /**
     * Return the Elasticsearch client instance.
     *
     * @return \Elasticsearch\Client
     */
    public function getClient();
}
