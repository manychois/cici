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
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return ['type' => 'bad-url'];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return 'url(bad-url)';
    }

    #endregion extends AbstractToken
}
