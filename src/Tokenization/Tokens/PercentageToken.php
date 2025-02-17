<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a percentage token.
 */
class PercentageToken extends AbstractNumericToken
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
            'type' => 'percentage',
            'value' => $this->value,
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return \strval($this->value) . '%';
    }

    #endregion extends AbstractNumericToken
}
