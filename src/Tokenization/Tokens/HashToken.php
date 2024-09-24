<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a hash token.
 */
class HashToken extends AbstractLiteralToken
{
    /**
     * Creates a new instance of HashToken.
     *
     * @param string $value    The identifier value after the hash sign.
     * @param bool   $isIdType Whether the hash token is an ID type.
     * @param int    $offset   The offset of the token.
     */
    public function __construct(string $value, public readonly bool $isIdType, int $offset)
    {
        parent::__construct($value, $offset);
    }

    #region extends AbstractLiteralToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'isIdType' => $this->isIdType,
            'type' => 'hash',
            'value' => $this->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        $pattern = '/[^' . self::IDENT_CODEPOINTS . ']/u';

        return '#' . self::escape($this->value, $pattern);
    }

    #endregion extends AbstractLiteralToken
}
