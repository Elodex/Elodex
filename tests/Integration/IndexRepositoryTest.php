<?php

namespace Integration;

use Elodex\IndexManager;
use Elodex\IndexRepository;
use Integration\IndexedModel;
use Illuminate\Support\Collection;

/**
 * @group integration
 */
class IndexRepositoryTest extends \PHPUnit_Framework_TestCase
{
    protected static $client;
    protected static $indexManager;
    protected $indexName;
    protected $indexRepository;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        static::$client = \Elasticsearch\ClientBuilder::fromConfig(['hosts' => ['localhost:9200']]);

        static::$indexManager = new IndexManager(static::$client);
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->indexName = strtolower(uniqid('phpunit_'));
        static::$indexManager->createIndex($this->indexName);

        $this->indexRepository = new IndexRepository(static::$client, IndexedModel::class, $this->indexName);
        $this->indexRepository->setShouldRefreshShard(true);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        static::$indexManager->deleteIndex($this->indexName);

        parent::tearDown();
    }

    public function testAddUpdateRemove()
    {
        $indexedModel = (new IndexedModel)->setId(1);

        // Add a model's indexed document to the index.
        $results = $this->indexRepository->add($indexedModel);
        $this->assertArraySubset(['_id' => $indexedModel->getIndexKey()], $results);
        $this->assertArraySubset(['created' => true], $results);

        // Update a model in the index.
        $results = $this->indexRepository->update($indexedModel);
        $this->assertArraySubset(['_id' => $indexedModel->getIndexKey()], $results);

        // Delete a model's document from the index.
        $results = $this->indexRepository->remove($indexedModel);
        $this->assertArraySubset(['_id' => $indexedModel->getIndexKey()], $results);
        $this->assertArraySubset(['found' => true], $results);
    }

    public function testGetDocument()
    {
        $indexedModel = (new IndexedModel)->setId(1);

        $results = $this->indexRepository->add($indexedModel);
        $this->assertArraySubset(['_id' => $indexedModel->getIndexKey()], $results);
        $this->assertArraySubset(['created' => true], $results);

        $results = $this->indexRepository->getDocument($indexedModel);
        $this->assertArrayHasKey('_source', $results);
        $this->assertEquals($indexedModel->toIndexDocument(), $results['_source']);
    }

    public function testGetAll()
    {
        $items = new Collection([
            (new IndexedModel)->setId(1),
            (new IndexedModel)->setId(2),
            (new IndexedModel)->setId(3),
        ]);
        $this->indexRepository->add($items);

        $results = $this->indexRepository->all();
        $resultIds = array_keys($results->all());

        $this->assertCount(3, $results);
        $this->assertEmpty(array_diff([1, 2, 3], $resultIds));
    }
}
