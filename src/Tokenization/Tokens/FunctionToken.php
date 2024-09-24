<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a function token.
 */
class FunctionToken extends AbstractLiteralToken
{
    #region extends AbstractLiteralToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->value,
            'type' => 'function',
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return self::escapeIdent($this->value) . '(';
    }

    #endregion extends AbstractLiteralToken
}
