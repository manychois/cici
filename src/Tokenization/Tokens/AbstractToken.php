<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a CSS token.
 */
abstract class AbstractToken implements \JsonSerializable, \Stringable
{
    public const NON_ASCII_IDENT_CODEPOINTS = '\x{00B7}\x{00C0}-\x{00D6}\x{00D8}-\x{00F6}\x{00F8}-\x{037D}'
        . '\x{037F}-\x{1FFF}\x{200C}\x{200D}\x{203F}\x{2040}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}'
        . '\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{10FFFF}';
    public const IDENT_START_CODEPOINTS = 'a-zA-Z_' . self::NON_ASCII_IDENT_CODEPOINTS;
    public const IDENT_CODEPOINTS = '-0-9' . self::IDENT_START_CODEPOINTS;
    public const NON_PRINTABLE_CODEPOINTS = '\x00-\x08\x0B\x0E-\x1F\x7F';

    /**
     * The string position at which the token starts.
     */
    public readonly int $offset;
    public readonly int $length;

    /**
     * Initializes a new instance of the AbstractToken class.
     *
     * @param int $offset The string position at which the token starts.
     * @param int $length The byte length of the token.
     */
    public function __construct(int $offset, int $length)
    {
        $this->offset = $offset;
        $this->length = $length;
    }

    /**
     * Escapes any matched characters in the specified literal string.
     *
     * @param string $literal The literal string to escape.
     * @param string $pattern The regular expression pattern to match characters to escape.
     *
     * @return string The escaped string.
     */
    final public static function escape(string $literal, string $pattern): string
    {
        $fn = static function (array $matches) {
            $chr = $matches[0];
            $code = \mb_ord($chr, 'UTF-8');
            if ($code === false) {
                throw new \InvalidArgumentException('The character is not a valid UTF-8 character.');
            }
            $escaped = \strtoupper(\dechex($code));

            return '\\' . $escaped . (\strlen($escaped) < 6 ? ' ' : '');
        };
        $replaced = \preg_replace_callback($pattern, $fn, $literal);
        \assert(\is_string($replaced));

        return $replaced;
    }

    /**
     * Escapes an identifier value.
     *
     * @param string $value The identifier value to escape.
     *
     * @return string The escaped identifier value.
     */
    final public static function escapeIdent(string $value): string
    {
        $pattern = '/[^-' . self::IDENT_START_CODEPOINTS . ']/u';
        $firstCh = \mb_substr($value, 0, 1, 'UTF-8');
        $firstCh = self::escape($firstCh, $pattern);

        $pattern = '/[^' . self::IDENT_CODEPOINTS . ']/u';
        $remaining = \mb_substr($value, 1, null, 'UTF-8');
        $remaining = self::escape($remaining, $pattern);

        return $firstCh . $remaining;
    }
}
