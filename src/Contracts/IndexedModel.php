<?php

namespace Elodex\Contracts;

interface IndexedModel extends IndexedDocument
{
    /**
     * Get the type name used for the indexed document.
     *
     * @return string
     */
    public function getIndexTypeName();

    /**
     * Get the value of the model's document index key.
     *
     * @return mixed
     */
    public function getIndexKey();

    /**
     * Returns the indexed relationships of the model.
     *
     * @return array
     */
    public function getIndexRelations();

    /**
     * Get the changed document data of this model instance used for incremental
     * updating of the index entry.
     *
     * @return array
     */
    public function getChangedIndexDocument();

    /**
     * Set the index document version.
     *
     * @param  int $version
     * @return $this
     */
    public function setIndexVersion($version);

    /**
     * Get the index document version.
     *
     * @return int|null
     */
    public function getIndexVersion();

    /**
     * Set the index score of the model.
     *
     * @param  float $score
     * @return $this
     */
    public function setIndexScore($score);

    /**
     * Get the index score of the model.
     *
     * @return float|null
     */
    public function getIndexScore();

    /**
     * Indicates if the model can be converted to it's document representation
     * and added to the index.
     *
     * @return bool
     */
    public function canAddToIndex();
}
