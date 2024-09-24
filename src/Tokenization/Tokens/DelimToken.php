<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a delimiter token.
 */
class DelimToken extends AbstractLiteralToken
{
    #region extends AbstractLiteralToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'type' => 'delim',
            'value' => $this->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->value;
    }

    #endregion extends AbstractLiteralToken
}
