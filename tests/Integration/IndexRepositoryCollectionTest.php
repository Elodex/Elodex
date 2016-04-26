<?php

namespace Integration;

use Elodex\IndexManager;
use Elodex\IndexRepository;
use Elodex\Exceptions\BulkOperationException;
use Illuminate\Support\Collection;
use Integration\IndexedModel;

/**
 * @group integration
 */
class IndexRepositoryCollectionTest extends \PHPUnit_Framework_TestCase
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

    public function testAddUpdateRemoveCollection()
    {
        $collection = new Collection([
            (new IndexedModel)->setId(1),
            (new IndexedModel)->setId(2),
            (new IndexedModel)->setId(3)
        ]);

        // Add collection
        $results = $this->indexRepository->add($collection);

        $this->assertArraySubset(['errors' => false], $results);
        $this->assertArrayHasKey('items', $results);
        $this->assertCount(3, $results['items']);

        // Update with collection
        $results = $this->indexRepository->update($collection);

        $this->assertArraySubset(['errors' => false], $results);
        $this->assertArrayHasKey('items', $results);
        $this->assertCount(3, $results['items']);

        // Delete collection
        $results = $this->indexRepository->remove($collection);

        $this->assertArraySubset(['errors' => false], $results);
        $this->assertArrayHasKey('items', $results);
        $this->assertCount(3, $results['items']);
    }

    public function testAddCollectionDuplicate()
    {
        $this->setExpectedException(BulkOperationException::class);

        $items = [
            (new IndexedModel)->setId(1),
            (new IndexedModel)->setId(2),
            (new IndexedModel)->setId(1)
        ];
        $collection = new Collection($items);

        try {
            $this->indexRepository->add($collection);
        } catch (BulkOperationException $ex) {
            $failedItems = $ex->getFailedItems();

            $this->assertCount(1, $failedItems);
            $this->assertContains($items[2], $failedItems, 'Missing failed item in bulk exception', false, true);

            throw $ex;
        }
    }

    public function testUpdateCollectionFailure()
    {
        $this->setExpectedException(BulkOperationException::class);

        $items = [
            (new IndexedModel)->setId(1),
            (new IndexedModel)->setId(2),
        ];
        $collection = new Collection($items);

        $this->indexRepository->add($collection);

        $newItem = (new IndexedModel)->setId(3);
        $collection->push($newItem);

        try {

            $this->indexRepository->update($collection);
        } catch (BulkOperationException $ex) {
            $failedItems = $ex->getFailedItems();

            $this->assertCount(1, $failedItems);
            $this->assertContains($newItem, $failedItems, 'Missing failed item in bulk exception', false, true);

            throw $ex;
        }
    }
}
