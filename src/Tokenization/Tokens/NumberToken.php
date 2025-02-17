<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a number token.
 */
class NumberToken extends AbstractNumericToken
{
    #region extends AbstractNumericToken

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'hasSign' => $this->hasSign,
            'isInt' => $this->isInt,
            'type' => 'number',
            'value' => $this->value,
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return \strval($this->value);
    }

    #endregion extends AbstractNumericToken
}
