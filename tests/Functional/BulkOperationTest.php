<?php

namespace Functional;

use Elodex\Exceptions\BulkOperationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
use Mockery as m;

/**
 * @group functional
 */
class BulkOperationTest extends \PHPUnit_Framework_TestCase
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

    public function testBulkOperationException()
    {
        $bulkItems = new Collection([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4],
        ]);

        $items = [
            [
                'create' => [
                    '_index' => 'my_index',
                    '_type' => 'my_type',
                    '_id' => 1,
                    'status' => 409,
                    'error' => [
                        'type' => 'document_already_exists_exception',
                        'reason' => '[my_index][1]: document already exists',
                        'shard' => '1',
                        'index' => 'my_index',
                    ],
                ],
            ],
            [
                'update' => [
                    '_index' => 'my_index',
                    '_type' => 'my_type',
                    '_id' => 2,
                    'status' => 404,
                    'error' => [
                        'type' => 'document_missing_exception',
                        'reason' => '[my_index][2]: document missing',
                        'shard' => '-1',
                        'index' => 'my_index',
                    ],
                ],
            ],
            [
                'delete' => [
                    '_index' => 'my_index',
                    '_type' => 'my_type',
                    '_id' => 3,
                    'status' => 404,
                    'found' => false,
                ],
            ],
            [
                'index' => [
                    '_index' => 'my_index',
                    '_type' => 'my_type',
                    '_id' => 4,
                    'status' => 200,
                ],
            ]
        ];

        $e = BulkOperationException::createForResults($items, $bulkItems->all());

        $failedItems = $e->getFailedItems();
        $failedIds = Arr::pluck($failedItems, 'id');

        $this->assertEquals([1, 2], $failedIds);
    }
}
