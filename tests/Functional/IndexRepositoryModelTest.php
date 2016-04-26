<?php

namespace Functional;

use Elodex\IndexRepository;
use Elodex\Contracts\IndexedModel;
use Mockery as m;

/**
 * @group functional
 */
class IndexRepositoryModelTest extends \PHPUnit_Framework_TestCase
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

    public function testRepositoryWithValidModel()
    {
        $client = m::mock(\Elasticsearch\Client::class);
        $client->shouldReceive('create')->andReturn([]);
        $client->shouldReceive('index')->andReturn([]);
        $client->shouldReceive('update')->andReturn([]);

        $indexRepository = new IndexRepository($client, IndexedModelA::class, 'phpunit');

        $r = $indexRepository->add(new IndexedModelA);
        $this->assertTrue(is_array($r));

        $r = $indexRepository->save(new IndexedModelA);
        $this->assertTrue(is_array($r));

        $r = $indexRepository->update(new IndexedModelA);
        $this->assertTrue(is_array($r));
    }

    public function testAddToRepositoryWithInvalidModel()
    {
        $client = m::mock(\Elasticsearch\Client::class);
        $client->shouldReceive('create')->andReturn([]);

        $indexRepository = new IndexRepository($client, IndexedModelA::class, 'phpunit');

        $this->expectException(\InvalidArgumentException::class);
        $indexRepository->add(new IndexedModelB);
    }

    public function testSaveToRepositoryWithInvalidModel()
    {
        $client = m::mock(\Elasticsearch\Client::class);
        $client->shouldReceive('index')->andReturn([]);

        $indexRepository = new IndexRepository($client, IndexedModelA::class, 'phpunit');

        $this->expectException(\InvalidArgumentException::class);
        $indexRepository->save(new IndexedModelB);
    }

    public function testRepositoryAddInvalidDerivedModel()
    {
        $client = m::mock(\Elasticsearch\Client::class);
        $client->shouldReceive('create')->andReturn([]);

        $indexRepository = new IndexRepository($client, IndexedModelA::class, 'phpunit');

        $this->expectException(\InvalidArgumentException::class);
        $r = $indexRepository->add(new IndexedModelC);
    }
}

class BaseIndexedModel implements IndexedModel
{
    protected static $id = 0;

    public function getIndexTypeName()
    {
        return get_class();
    }

    public function getIndexKey()
    {
        return static::$id++;
    }

    public function getIndexRelations()
    {
        return [];
    }

    public function setIndexVersion($version)
    {
        return $this;
    }

    public function getIndexVersion()
    {
        return null;
    }

    public function setIndexScore($score)
    {
        return $this;
    }

    public function getIndexScore()
    {
        return 1.0;
    }

    public function toIndexDocument()
    {
        return ['foo' => 'bar', 'test' => 1];
    }

    public function getChangedIndexDocument()
    {
        return ['foo' => 'bar'];
    }

    public function canAddToIndex()
    {
        return true;
    }
}

class IndexedModelA extends BaseIndexedModel
{
}

class IndexedModelB extends BaseIndexedModel
{
}

class IndexedModelC extends IndexedModelA
{
}
