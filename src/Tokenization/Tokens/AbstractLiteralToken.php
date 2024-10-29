<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a token that contains a string value.
 */
abstract class AbstractLiteralToken extends AbstractToken
{
    /**
     * Creates a new instance of string-value token.
     *
     * @param string $value  The string value.
     * @param int    $offset The string position at which the token starts.
     * @param int    $length The byte length of the token.
     */
    public function __construct(public readonly string $value, int $offset, int $length)
    {
        parent::__construct($offset, $length);
    }
}
