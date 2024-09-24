<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a URL token.
 */
class UrlToken extends AbstractLiteralToken
{
    #region extends AbstractLiteralToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'type' => 'url',
            'value' => $this->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $pattern = '/[' . \preg_quote('"\'()\\', '/') . '\\s' . self::NON_PRINTABLE_CODEPOINTS . ']/u';

        return \sprintf('url(%s)', self::escape($this->value, $pattern));
    }

    #endregion extends AbstractLiteralToken
}
