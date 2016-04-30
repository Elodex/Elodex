<?php

namespace Elodex;

use Illuminate\Container\Container;

trait IndexManagement
{
    use IndexMapping;

    /**
     * Get the default index manager.
     *
     * @return \Elodex\IndexManager
     */
    public static function getDefaultIndexManager()
    {
        return Container::getInstance()->make('elodex.index', []);
    }

    /**
     * Convenient method to access the index manager of the current class and
     * open the index.
     *
     * @param  string $indexName
     * @return array
     */
    public static function openIndex($indexName = null)
    {
        $index = static::getDefaultIndexManager();
        $indexName = $indexName ?: $index->getDefaultIndex();

        return $index->openIndex($indexName);
    }

    /**
     * Convenient method to access the index manager of the current class and
     * close the index.
     *
     * @param  string $indexName
     * @return array
     */
    public static function closeIndex($indexName = null)
    {
        $index = static::getDefaultIndexManager();
        $indexName = $indexName ?: $index->getDefaultIndex();

        return $index->closeIndex($indexName);
    }

    /**
     * Convenient method to access the index manager of the current class and
     * put index settings.
     *
     * @param  array $settings
     * @param  string|null $indexName
     * @return array
     */
    public static function putIndexSettings(array $settings, $indexName = null)
    {
        $index = static::getDefaultIndexManager();
        $indexName = $indexName ?: $index->getDefaultIndex();

        return $index->putSettings($settings, $indexName);
    }

    /**
     * Convenient method to access the index manager of the current class and
     * put the property mappings of a type into the index.
     *
     * @param  string|null $indexName
     * @return array
     */
    public static function putIndexMappings($indexName = null)
    {
        $index = static::getDefaultIndexManager();
        $indexName = $indexName ?: $index->getDefaultIndex();

        $instance = new static;

        $mappings = $instance->getIndexMappingProperties();

        return $index->putMappings($indexName, $instance->getIndexTypeName(), $mappings);
    }
}
