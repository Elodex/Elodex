<?php

namespace Functional;

use Functional\MappingModel;
use Illuminate\Support\Arr;
use Mockery as m;

/**
 * @group functional
 */
class IndexMappingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        m::close();

        parent::tearDown();
    }

    public function testVisibleMapping()
    {
        $model = new IndexMappingSimpleModel;

        $model->setDates(['date1', 'date2', 'date3']);
        $model->setCasts(['cast1' => 'int', 'cast2' => 'float']);

        $visible = ['date1', 'cast2'];
        $model->setVisible($visible);

        $mappings = $model->getIndexMappingProperties();

        $this->assertEquals($visible, array_keys($mappings));
    }

    public function testHiddenMapping()
    {
        $model = new IndexMappingSimpleModel;

        $model->setDates(['date1', 'date2', 'date3']);
        $model->setCasts(['cast1' => 'int', 'cast2' => 'float']);

        $hidden = ['date1', 'cast2'];
        $model->setHidden($hidden);

        $mappings = $model->getIndexMappingProperties();

        $mappingKeys = array_keys($mappings);

        $this->assertContains('cast1', $mappingKeys);
        $this->assertNotContains('cast2', $mappingKeys);
        $this->assertNotContains('date1', $mappingKeys);
        $this->assertContains('date2', $mappingKeys);
        $this->assertContains('date3', $mappingKeys);
    }

    public function testDatesMapping()
    {
        $model = new IndexMappingSimpleModel;

        $model->setDates(['date1', 'date2']);
        $model->setCasts(['date3' => 'date']);

        $mappings = $model->getIndexMappingProperties();

        $this->assertArrayHasKey('date1', $mappings);
        $this->assertArrayHasKey('date2', $mappings);
        $this->assertArrayHasKey('date3', $mappings);
        $this->assertArrayHasKey('created_at', $mappings);
        $this->assertArrayHasKey('updated_at', $mappings);

        $dateMapping = [
            'type' => 'date',
            'format' => 'yyyy-MM-dd HH:mm:ss',
        ];
        $this->assertEquals($dateMapping, $mappings['date1']);
        $this->assertEquals($dateMapping, $mappings['date2']);
        $this->assertEquals($dateMapping, $mappings['date3']);
        $this->assertEquals($dateMapping, $mappings['created_at']);
        $this->assertEquals($dateMapping, $mappings['updated_at']);
    }

    public function testCastsMapping()
    {
        $model = new IndexMappingSimpleModel;

        $casts = [
            'f1' => 'int',
            'f2' => 'integer',
            'f3' => 'real',
            'f4' => 'float',
            'f5' => 'double',
            'f6' => 'string',
            'f7' => 'bool',
            'f8' => 'boolean',
            'f9' => 'object',
            'f10' => 'array',
            'f11' => 'json',
            'f12' => 'collection',
            'f13' => 'date',
            'f14' => 'datetime',
            'f15' => 'timestamp',
        ];
        $model->setCasts($casts);

        $mappings = $model->getIndexMappingProperties();

        // Test if all fields got mapped
        $this->assertEmpty(array_diff(array_keys($casts), array_keys($mappings)), 'Missing mappings');

        // Test if all fields have the expected mappings
        $expectedMappings = [
            'f1' => 'integer',
            'f2' => 'integer',
            'f3' => 'float',
            'f4' => 'float',
            'f5' => 'double',
            'f6' => 'string',
            'f7' => 'boolean',
            'f8' => 'boolean',
            'f9' => 'object',
            'f10' => 'object',
            'f11' => 'object',
            'f12' => 'object',
            'f13' => 'date',
            'f14' => 'date',
            'f15' => 'date',
        ];

        foreach ($expectedMappings as $key => $type) {
            $this->assertEquals($type, $mappings[$key]['type'], "Expected mapping '{$type}' for '{$key}' did not match");
        }
    }

    public function testCustomMapping()
    {
        $model = new IndexMappingSimpleModel;

        $casts = [
            'foo' => 'integer',
            'bar' => 'string',
        ];
        $model->setCasts($casts);

        $customMapping = [
            'foo' => [
                'type' => 'string',
                'analyzer' => 'simple',
            ],
        ];
        $model->setCustomIndexMappingProperties($customMapping);

        $mappings = $model->getIndexMappingProperties();

        $this->assertEquals($customMapping['foo'], $mappings['foo']);
        $this->assertEquals('string', $casts['bar']);
    }

    public function testRelationsMappings()
    {
        $parent = new IndexMappingParentModel;
        $parent->setCasts(['foo' => 'string']);

        $parent->setIndexRelations(['related']);

        $mappings = $parent->getIndexMappingProperties();

        $this->assertArrayHasKey('related', $mappings);
        $this->assertArrayHasKey('type', $mappings['related']);
        $this->assertArrayHasKey('properties', $mappings['related']);

        $this->assertEquals('nested', $mappings['related']['type']);

        $relatedPropertyMappings = array_keys($mappings['related']['properties']);
        $this->assertEmpty(array_diff(['foo', 'bar'], $relatedPropertyMappings), 'Missing related property mappings');
    }

    public function testNestedRelationMappings()
    {
        $parent = new IndexMappingParentModel;
        $parent->setCasts(['foo' => 'string']);

        $parent->setIndexRelations(['related', 'related.related']);

        $mappings = $parent->getIndexMappingProperties();

        $this->assertArrayHasKey('related', $mappings);
        $nestedPropertyMappings = Arr::get($mappings, 'related.properties.related.properties');
        $this->assertNotNull($nestedPropertyMappings);

        $customBarMapping = Arr::get($parent->getCustomIndexMappingProperties(), 'related.properties.bar');
        $this->assertEquals($customBarMapping, Arr::get($mappings, 'related.properties.bar'));
    }

}

class IndexMappingSimpleModel extends MappingModel
{
    protected $table = 'phpunit_IndexMappingSimpleModel';
}

class IndexMappingParentModel extends MappingModel
{
    protected $table = 'phpunit_IndexMappingParentModel';
    protected $indexMappingProperties = [
        'related' => [
            'properties' => [
                'bar' => [
                    'type' => 'string',
                    'analyzer' => 'simple',
                ],
            ],
        ],
    ];

    public function related()
    {
        $mock = m::mock(\Illuminate\Database\Eloquent\Relations\Relation::class);
        $mock->shouldReceive('getRelated')->andReturn(new IndexMappingRelatedModel);

        return $mock;
    }
}

class IndexMappingRelatedModel extends MappingModel
{
    protected $table = 'phpunit_IndexMappingRelatedModel';
    protected $casts = [
        'foo' => 'integer',
        'bar' => 'string',
    ];

    public function related()
    {
        $mock = m::mock(\Illuminate\Database\Eloquent\Relations\Relation::class);
        $mock->shouldReceive('getRelated')->andReturn(new IndexMappingRelatedModel);

        return $mock;
    }
}
