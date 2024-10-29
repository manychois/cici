<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a token that contains a numeric value.
 */
abstract class AbstractNumericToken extends AbstractToken
{
    /**
     * The numeric value.
     */
    public readonly int|float $value;
    /**
     * Whether the value is an integer.
     */
    public readonly bool $isInt;
    /**
     * Whether the source string has a positive or negative sign.
     */
    public readonly bool $hasSign;

    /**
     * Creates a new instance of numeric-value token.
     *
     * @param int|float $value   The numeric value.
     * @param bool      $isInt   Whether the value is an integer.
     * @param bool      $hasSign Whether the value has a sign.
     * @param int       $offset  The offset of the token.
     * @param int       $length  The byte length of the token.
     */
    public function __construct(int|float $value, bool $isInt, bool $hasSign, int $offset, int $length)
    {
        parent::__construct($offset, $length);

        $this->value = $value;
        $this->isInt = $isInt;
        $this->hasSign = $hasSign;
    }

    /**
     * Gets the integer value of the token.
     *
     * @return int The integer value.
     *
     * @codeCoverageIgnore
     */
    public function intVal(): int
    {
        \assert($this->isInt);

        return (int) $this->value;
    }
}
