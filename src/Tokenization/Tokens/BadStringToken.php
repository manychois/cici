<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a bad-string token.
 */
class BadStringToken extends AbstractToken
{
    #region extends AbstractToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return ['type' => 'bad-string'];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '"bad-string"';
    }

    #endregion extends AbstractToken
}
