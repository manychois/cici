<?php

declare(strict_types=1);

namespace Manychois\Cici\Exceptions;

/**
 * Represents a collection of parse exceptions.
 *
 * @implements \IteratorAggregate<int,ParseException>
 */
class ParseExceptionCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<int,ParseException>
     */
    private array $exceptions = [];

    /**
     * Adds a parse exception to the collection.
     *
     * @param ParseException $exception The parse exception to add.
     */
    public function add(ParseException $exception): void
    {
        $this->exceptions[] = $exception;
    }

    /**
     * Gets the parse exception at the specified index.
     *
     * @param int $index The zero-based index of the parse exception to get.
     *
     * @return ParseException The parse exception at the specified index.
     */
    public function get(int $index): ParseException
    {
        if ($index < 0 || $index >= \count($this->exceptions)) {
            throw new \OutOfBoundsException('Index out of range.');
        }

        return $this->exceptions[$index];
    }

    #region implement \Countable

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return \count($this->exceptions);
    }

    #endregion

    #region implement \IteratorAggregate

    /**
     * @inheritDoc
     *
     * @return \ArrayIterator<int,ParseException>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->exceptions);
    }

    #endregion
}
