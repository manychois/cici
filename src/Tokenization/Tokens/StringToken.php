<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a string token.
 */
class StringToken extends AbstractLiteralToken
{
    #region extends AbstractLiteralToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return ['type' => 'string', 'value' => $this->value];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return \sprintf('"%s"', self::escape($this->value, '/["\\n\\\\]/'));
    }

    #endregion extends AbstractLiteralToken
}
