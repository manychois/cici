<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a whitespace token.
 */
class WhitespaceToken extends AbstractToken
{
    #region extends AbstractToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return ' ';
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return ' ';
    }

    #endregion extends AbstractToken
}
