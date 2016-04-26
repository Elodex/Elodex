<?php

namespace Elodex;

use Elodex\Contracts\IndexedDocument;
use Illuminate\Support\Str;
use Illuminate\Contracts\Support\Arrayable;

trait IndexedModel
{
    use IndexManagement,
        IndexRepositoryAccess;

    /**
     * The relationships that should be added to the index document.
     *
     * @var array
     */
    protected $indexRelations = [];

    /**
     * The version of the model's document in the index.
     *
     * @var int|null
     */
    protected $indexVersion;

    /**
     * The index score of the model as a result of an index search.
     *
     * @var float|null
     */
    protected $indexScore;

    /**
     * {@inheritdoc}
     */
    public function getIndexTypeName()
    {
        return $this->getTable();
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexKey()
    {
        return $this->getKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexRelations()
    {
        return $this->indexRelations;
    }

    /**
     * {@inheritdoc}
     */
    public function toIndexDocument()
    {
        // Temporarily disable all whitelisting and blacklisting for relations.
        $visible = $this->getVisible();
        $hidden = $this->getHidden();
        $this->hideNonIndexRelations();

        // Load all needed relations for the index document.
        $this->loadIndexRelations();

        // Convert the model to a document representation.
        $document = array_merge($this->attributesToArray(), $this->indexRelationsDocuments());

        // Restore whitelist and blacklist properties.
        $this->setHidden($hidden);
        $this->setVisible($visible);

        return $document;
    }

    /**
     * Get an array of documents for the index relations.
     *
     * @return array
     */
    public function indexRelationsDocuments()
    {
        $documents = [];

        foreach ($this->indexRelations as $relation) {
            $related = $this->relations[$relation];

            // Check if the related instance implements the indexed document
            // interface to create a document representation.
            if ($related instanceof IndexedDocument) {
                $document = $related->toIndexDocument();
            }

            // Fallback to the Arrayable interface and its toArray method.
            elseif ($related instanceof Arrayable) {
                $document = $related->toArray();
            }

            // If the value is null, we'll still go ahead and set it in this list of
            // attributes since null is used to represent empty relationships if
            // if it a has one or belongs to type relationships on the models.
            elseif (is_null($related)) {
                $document = $related;
            }

            // Make sure the relation name is snake-cased if needed.
            if (static::$snakeAttributes) {
                $relation = Str::snake($relation);
            }

            if (isset($relation) || is_null($related)) {
                $documents[$relation] = $document;
            }

            unset($document);
        }

        return $documents;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangedIndexDocument()
    {
        // Note that indexed relations are only loaded for complete documents,
        // there's no reliable way to know which relations might have changed.
        // Check which attributes are marked as 'dirty'.
        $dirtyAttributes = array_keys($this->getDirty());
        if (empty($dirtyAttributes)) {
            return [];
        }

        // Use temporary whitelisting to exclude all attributes that have not changed.
        $visible = $this->getVisible();
        $this->setVisible($dirtyAttributes);

        // Convert the dirty attributes to an array.
        // Note that no relationships are being included here since there's no
        // tight binding between related models and their parents.
        $document = $this->attributesToArray();

        // Restore the visible attributes on the model.
        $this->setVisible($visible);

        return $document;
    }

    /**
     * Set the index document version.
     *
     * @param  int $version
     * @return $this
     */
    public function setIndexVersion($version)
    {
        $this->indexVersion = $version;

        return $this;
    }

    /**
     * Get the index document version.
     *
     * @return int|null
     */
    public function getIndexVersion()
    {
        return $this->indexVersion;
    }

    /**
     * Set the index score of the model.
     *
     * @param  float $score
     * @return $this
     */
    public function setIndexScore($score)
    {
        $this->indexScore = $score;

        return $this;
    }

    /**
     * Get the index score of the model.
     *
     * @return float|null
     */
    public function getIndexScore()
    {
        return $this->indexScore;
    }

    /**
     * {@inheritdoc}
     */
    public function canAddToIndex()
    {
        return $this->exists;
    }

    /**
     * Hides all relations which are not meant to be part of the indexed document.
     *
     * @return void
     */
    protected function hideNonIndexRelations()
    {
        // Make sure all relations which may have been loaded and which
        // shouldn't be added to the document are being hidden.
        $loadedRelations = array_keys($this->relations);
        $hiddenRelations = array_diff($loadedRelations, $this->indexRelations);

        if (empty($hiddenRelations)) {
            return;
        }

        // Remove all visible non-indexed relations from the whitelist.
        $visible = $this->getVisible();
        if (count($visible) > 0) {
            $this->setVisible(array_diff($visible, $hiddenRelations));

            return;
        }

        // Add the non-indexed relations to the blacklist.
        $this->addHidden($hiddenRelations);
    }

    /**
     * Load all relations relevant for the indexed document.
     *
     * @return void
     */
    protected function loadIndexRelations()
    {
        // Do not load any index relations if the indicating property is null on
        // the model instance. This does give subclasses the opportunity to disable
        // this functionality without having to override the method.
        if (! empty($this->indexRelations)) {
            // Don't load already loaded relations
            $this->load(array_diff($this->indexRelations, array_keys($this->relations)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }
}
