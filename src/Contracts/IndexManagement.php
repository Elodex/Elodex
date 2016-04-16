<?php

namespace Elodex\Contracts;

interface IndexManagement
{
    /**
     * Get the default index manager.
     *
     * @return \Elodex\IndexManager
     */
    public static function getDefaultIndexManager();

    /**
     * Convenient method to access the index manager of the current class and
     * open the index.
     *
     * @param  string $indexName
     * @return array
     */
    public static function openIndex($indexName = null);

    /**
     * Convenient method to access the index manager of the current class and
     * close the index.
     *
     * @param  string $indexName
     * @return array
     */
    public static function closeIndex($indexName = null);

    /**
     * Convenient method to access the index manager of the current class and
     * put index settings.
     *
     * @param  array $settings
     * @param  string|null $indexName
     * @return array
     */
    public static function putIndexSettings(array $settings, $indexName = null);

    /**
     * Convenient method to access the index manager of the current class and
     * put the property mappings of a type into the index.
     *
     * @param  string|null $indexName
     * @return array
     */
    public static function putIndexMappings($indexName = null);
}
