<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a dimension token.
 */
class DimensionToken extends AbstractNumericToken
{
    public readonly string $unit;

    /**
     * Creates a new instance of the DimensionToken class.
     *
     * @param int|float $value   The value of the dimension.
     * @param string    $unit    The unit of the dimension.
     * @param bool      $isInt   Whether the value is an integer.
     * @param bool      $hasSign Whether the value has a sign.
     * @param int       $offset  The string position at which the token starts.
     * @param int       $length  The byte length of the token.
     */
    public function __construct(int|float $value, string $unit, bool $isInt, bool $hasSign, int $offset, int $length)
    {
        parent::__construct($value, $isInt, $hasSign, $offset, $length);

        $this->unit = $unit;
    }

    #region extends AbstractNumericToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'hasSign' => $this->hasSign,
            'isInt' => $this->isInt,
            'type' => 'dimension',
            'unit' => $this->unit,
            'value' => $this->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return \strval($this->value) . self::escapeIdent($this->unit);
    }

    #endregion extends AbstractNumericToken
}
