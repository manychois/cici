<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a bad-URL token.
 */
class BadUrlToken extends AbstractToken
{
    #region extends AbstractToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return ['type' => 'bad-url'];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return 'url(bad-url)';
    }

    #endregion extends AbstractToken
}
