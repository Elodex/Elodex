<?php

namespace Elodex\Exceptions;

class MultiGetException extends \Exception
{
    /**
     * Dictionary of failed model items.
     *
     * @var array
     */
    protected $failedItems = [];

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
     * Create a new instance for the result failures.
     *
     * @param  array $items
     * @param  array $errors
     * @return static
     */
    public static function createForFailedItems(array $items, array $errors)
    {
        $message = '';
        foreach ($errors as $id => $error) {
            $message .= "Multi GET failed for ID {$id}: {$error['reason']}\n";
        }

        $instance = new static($message);

        $instance->failedItems = $items;

        return $instance;
    }
}
