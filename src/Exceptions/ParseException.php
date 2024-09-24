<?php

declare(strict_types=1);

namespace Manychois\Cici\Exceptions;

/**
 * Represents a parse error due to an invalid CSS syntax.
 */
final class ParseException extends \InvalidArgumentException
{
    /**
     * The character position where the parse error occurred.
     */
    public readonly int $position;

    /**
     * Initializes a new instance of the ParseException class.
     *
     * @param string $message  The message of the exception.
     * @param int    $position The character position where the parse error occurred.
     */
    public function __construct(string $message, int $position)
    {
        parent::__construct($message);

        $this->position = $position;
    }
}
