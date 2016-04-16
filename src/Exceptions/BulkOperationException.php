<?php

namespace Elodex\Exceptions;

use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Elasticsearch\Common\Exceptions\Conflict409Exception;
use Elasticsearch\Common\Exceptions\Forbidden403Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\RequestTimeout408Exception;

class BulkOperationException extends \Exception
{
    /**
     * Dictionary of failed model items.
     *
     * @var array
     */
    protected $failedItems = [];

    /**
     * Exceptions dictionary of failed model items.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Process the error results for a bulk operations on the specified items.
     *
     * @param  array $resultItems
     * @param  array $bulkItems
     * @return void
     */
    protected function processResults(array $resultItems, array $bulkItems)
    {
        $bulkItems = array_values($bulkItems);

        foreach ($resultItems as $key => $item) {
            $operation = reset($item);
            $statusCode = $operation['status'];

            // Check if this item has failed.
            if (isset($operation['error'])) {
                $error = json_encode($operation['error']);

                if ($statusCode === 400) {
                    $exception = new BadRequest400Exception($error, $statusCode);
                } elseif ($statusCode === 403) {
                    $exception = new Forbidden403Exception($error, $statusCode);
                } elseif ($statusCode === 404) {
                    $exception = new Missing404Exception($error, $statusCode);
                } elseif ($statusCode === 409) {
                    $exception = new Conflict409Exception($error, $statusCode);
                } elseif ($statusCode === 408) {
                    $exception = new RequestTimeout408Exception($error, $statusCode);
                } else {
                    $exception = new \Exception($error, $statusCode);
                }

                $itemId = $operation['_id'];
                $this->failedItems[$itemId] = $bulkItems[$key];

                $this->errors[$itemId] = $exception;
            }
        }
    }

    /**
     * Public accessor to the dictionary of failed items.
     *
     * @return array
     */
    public function getFailedItems()
    {
        return $this->failedItems;
    }

    /**
     * Public accessor to the exceptions dictionary of the failed items.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Create a new instance for the result failures.
     *
     * @param  array $items
     * @param  array $bulkItems
     * @return static
     */
    public static function createForResults(array $items, array $bulkItems)
    {
        $instance = new static;

        $instance->processResults($items, $bulkItems);

        $errors = [];
        foreach ($instance->getErrors() as $e) {
            $errors[] = $e->getMessage();
        }

        $instance->message = implode("\n", $errors);

        return $instance;
    }
}
