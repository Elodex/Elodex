<?php

namespace Elodex;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Support\Str;
use Carbon\Carbon;

trait IndexMapping
{
    /**
     * Additional custom property mappings used for the index.
     *
     * @var array
     */
    protected $indexMappingProperties = [];

    /**
     * Formatting used for the mapping of date properties.
     *
     * @var string
     */
    protected $indexMappingDateFormat = 'yyyy-MM-dd HH:mm:ss';

    /**
     * Get the mapping properties of the current instance used for the index.
     *
     * @return array
     */
    public function getIndexMappingProperties()
    {
        return $this->getIndexMappingPropertiesForModel($this, $this->indexRelations, $this->indexMappingProperties);
    }

    /**
     * Get the mapping properties for a model used for the index.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array $indexRelations
     * @param  array $customMappings
     * @return array
     */
    public function getIndexMappingPropertiesForModel(BaseModel $model, array $indexRelations = [], array $customMappings = [])
    {
        // Merge the property mappings of all nested documents (relations)
        // with the mapping of the attributes of this instance.
        $merged = array_merge(
            $this->getIndexRelationsMappingProperties($model, $indexRelations),
            $this->getDefaultIndexMappingProperties($model)
        );

        // Custom mappings may overwrite any mapping.
        return array_replace_recursive($merged, $customMappings);
    }

    /**
     * Returns a default property mapping for the index based on the casts
     * property.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function getDefaultIndexMappingProperties(BaseModel $model)
    {
        $mappings = [];

        // Process all date attributes
        $dateFormat = $model->getDateFormat();
        if ($dateFormat === Carbon::DEFAULT_TO_STRING_FORMAT) {
            $dateAttributes = $this->getVisibleIndexDates($model);

            foreach ($dateAttributes as $key) {
                $mappings[$key] = [
                    'type' => 'date',
                    'format' => $this->indexMappingDateFormat,
                ];
            }
        }

        // Process all casts
        $castAttributes = $this->getVisibleIndexCasts($model);
        foreach ($castAttributes as $key => $value) {
            $indexType = $this->getIndexAttributeType($model, $key);

            if (! is_null($indexType)) {
                $mappings[$key] = ['type' => $indexType];
                if ($indexType === 'date') {
                    $mappings[$key]['format'] = $this->indexMappingDateFormat;
                }
            }
        }

        return $mappings;
    }

    /**
     * Get an array of mapping properties for all index relevant relations.
     *
     * @param  array $indexRelations
     * @param  bool $useNestedType
     * @return array
     */
    protected function getIndexRelationsMappingProperties(BaseModel $model, array $indexRelations = [], $useNestedType = true)
    {
        if (empty($indexRelations)) {
            return [];
        }

        // Walk through all relations of the specified model to build the needed mappings.
        $mappings = [];

        foreach ($indexRelations as $relation) {
            // Check for a nested relation.
            if (($p = strpos($relation, '.')) !== false) {
                // Extract the first relation from the dot syntax and the remainder
                // which are the child relations.
                $relatedIndexRelations = [substr($relation, $p + 1)];
                $relation = substr($relation, 0, $p);
            } else {
                $relatedIndexRelations = [];
            }

            // Get the mappings for the properties of the related model.
            $relationMappings = $this->getIndexMappingsForRelation($model, $relation, $relatedIndexRelations);

            if (static::$snakeAttributes) {
                $key = Str::snake($relation);
            }

            // Note that the parent of a relationship is always responsible
            // for the creation of the mapping properties of its children.
            // This is because the index document has a nested structure but
            // no relationships.
            $mappings[$key] = [
                'properties' => $relationMappings,
            ];

            // Check if we should use nested object mappings which would create
            // hidden documents for the relationship documents.
            $mappings[$key]['type'] = $useNestedType ? 'nested' : 'object';
        }

        return $mappings;
    }

    /**
     * Get the index property mappings for the relation of the specified parent
     * model.
     *
     * @param  \Illuminate\Database\Eloquent\Model $parent
     * @param  string $relation
     * @param  array $relatedIndexRelations
     * @return array
     */
    protected function getIndexMappingsForRelation($parent, $relation, array $relatedIndexRelations = [])
    {
        // Create the relation instance.
        $relationInstance = $parent->$relation();

        // Get an instance of the related model of the relation.
        $relatedModel = $relationInstance->getRelated();

        if (property_exists($relatedModel, 'indexMappingProperties')) {
            $customRelationMappings = $relatedModel->indexMappingProperties;
        } else {
            $customRelationMappings = [];
        }

        // Recursive call to load all index property mappings of the related model.
        return $this->getIndexMappingPropertiesForModel($relatedModel, $relatedIndexRelations, $customRelationMappings);
    }

    /**
     * Get the visible model date attributes for the index.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function getVisibleIndexDates(BaseModel $model)
    {
        if (count($model->getVisible()) > 0) {
            return array_intersect($model->getDates(), $model->getVisible());
        }

        return array_diff($model->getDates(), $model->getHidden());
    }

    /**
     * Get the visible model casts for the index.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function getVisibleIndexCasts(BaseModel $model)
    {
        if (count($model->getVisible()) > 0) {
            return array_intersect_key($model->getCasts(), array_flip($model->getVisible()));
        }

        return array_diff_key($model->getCasts(), array_flip($model->getHidden()));
    }

    /**
     * Map the type of an Eloquent model attribute to an index attribute type.
     *
     * https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#mapping-type
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string $key
     * @return string|null
     */
    protected function getIndexAttributeType(BaseModel $model, $key)
    {
        switch ($model->getCastType($key)) {
            case 'int':
            case 'integer':
                return 'integer';

            case 'real':
            case 'float':
                return 'float';

            case 'double':
                return 'double';

            case 'string':
                return 'string';

            case 'bool':
            case 'boolean':
                return 'boolean';

            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'date';

            case 'array':
            case 'collection':
            case 'object':
            case 'json':
                return 'object';

            default:
                break;
        }

        return null;
    }
}
