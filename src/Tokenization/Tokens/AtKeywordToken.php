<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents an at-keyword token.
 */
class AtKeywordToken extends AbstractLiteralToken
{
    #region extends AbstractLiteralToken

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return ['type' => 'at-keyword', 'value' => $this->value];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return '@' . self::escapeIdent($this->value);
    }

    #endregion extends AbstractLiteralToken
}
