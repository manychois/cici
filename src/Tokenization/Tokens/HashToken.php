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
     * @param int    $offset   The string position at which the token starts.
     * @param int    $length   The byte length of the token.
     */
    public function __construct(string $value, public readonly bool $isIdType, int $offset, int $length)
    {
        parent::__construct($value, $offset, $length);
    }

    #region extends AbstractLiteralToken

    /**
     * @inheritDoc
     */
    #[\Override]
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
    #[\Override]
    public function __toString(): string
    {
        $pattern = '/[^' . self::IDENT_CODEPOINTS . ']/u';

        return '#' . self::escape($this->value, $pattern);
    }

    #endregion extends AbstractLiteralToken
}
