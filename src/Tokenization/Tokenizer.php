<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization;

use Manychois\Cici\Tokenization\Tokens\AbstractNumericToken;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;
use Manychois\Cici\Tokenization\Tokens\AtKeywordToken;
use Manychois\Cici\Tokenization\Tokens\BadStringToken;
use Manychois\Cici\Tokenization\Tokens\BadUrlToken;
use Manychois\Cici\Tokenization\Tokens\DelimToken;
use Manychois\Cici\Tokenization\Tokens\DimensionToken;
use Manychois\Cici\Tokenization\Tokens\FunctionToken;
use Manychois\Cici\Tokenization\Tokens\HashToken;
use Manychois\Cici\Tokenization\Tokens\IdentToken;
use Manychois\Cici\Tokenization\Tokens\NumberToken;
use Manychois\Cici\Tokenization\Tokens\PercentageToken;
use Manychois\Cici\Tokenization\Tokens\StringToken;
use Manychois\Cici\Tokenization\Tokens\Symbol;
use Manychois\Cici\Tokenization\Tokens\SymbolToken;
use Manychois\Cici\Tokenization\Tokens\UnicodeRangeToken;
use Manychois\Cici\Tokenization\Tokens\UrlToken;
use Manychois\Cici\Tokenization\Tokens\WhitespaceToken;
use Manychois\Cici\Utilities\RegexResult;

/**
 * Tokenizes a string for further CSS parsing.
 */
class Tokenizer
{
    private readonly string $canStartIdentSeqRegex;
    private readonly string $canStartHashRegex;
    private readonly string $identCodePointRegex;
    private readonly string $identStartCodePointRegex;
    private readonly string $isValidEscRegex;
    private readonly string $urlTokenStopRegex;

    /**
     * Initializes a new instance of the Tokenizer class.
     */
    public function __construct()
    {
        $validEscape = '\\\\[^\\n]';
        $identStartCodePoint = AbstractToken::IDENT_START_CODEPOINTS;
        $identCodePoint = AbstractToken::IDENT_CODEPOINTS;
        $nonPrintable = AbstractToken::NON_PRINTABLE_CODEPOINTS;

        $this->canStartIdentSeqRegex = "/\\G(--|-?[{$identStartCodePoint}]|-?{$validEscape})/u";
        $this->canStartHashRegex = "/\\G([{$identCodePoint}]|{$validEscape})/u";
        $this->identCodePointRegex = "/\\G[{$identCodePoint}]+/u";
        $this->identStartCodePointRegex = "/^[{$identStartCodePoint}]$/u";
        $this->isValidEscRegex = "/\\G{$validEscape}/u";
        $this->urlTokenStopRegex = "/[ \\t\\n()'\"\\\\{$nonPrintable}]/";
    }

    /**
     * Consumes any consecutive comments at the current position.
     *
     * @param TextStream $textStream The text stream to consume comments from.
     */
    public function consumeComments(TextStream $textStream): void
    {
        while ($textStream->peek(2) === '/*') {
            $pos = \strpos($textStream->text, '*/', $textStream->position);

            if ($pos === false) {
                $textStream->recordParseException('Unterminated comment.');
                $textStream->position = $textStream->length;

                break;
            }

            $textStream->position = $pos + 2;
        }
    }

    /**
     * Consumes an escaped code point at the current position.
     * This assumes the reverse solidus "\\" has already been consumed and the current character is not a newline.
     *
     * @param TextStream $textStream The text stream to consume the escaped code point from.
     *
     * @return string The unescaped code point.
     */
    public function consumeEscapedCodePoint(TextStream $textStream): string
    {
        \assert($textStream->peek(1) !== '\\');
        $matchResult = $textStream->matchRegex('/\G[\dA-Fa-f]{1,6}[ \t\n]?/');
        if ($matchResult->success) {
            $codeHex = \rtrim($matchResult->value);
            $codeValue = \intval(\hexdec($codeHex));
            if (
                $codeValue === 0 ||
                $codeValue >= 0xD800 && $codeValue <= 0xDBFF ||
                $codeValue >= 0xDC00 && $codeValue <= 0xDFFF ||
                $codeValue > 0x10FFFF
            ) {
                $codeHex = \strtoupper(\dechex($codeValue));
                $textStream->recordParseException(\sprintf('Invalid unicode code point U+%s.', $codeHex));
                $textStream->position += \strlen($matchResult->value);

                return "\u{FFFD}";
            }

            $chr = \mb_chr($codeValue);
            \assert($chr !== false);
            $textStream->position += \strlen($matchResult->value);

            return $chr;
        }

        if (!$textStream->hasMore()) {
            $textStream->recordParseException();

            return "\u{FFFD}";
        }

        $substr = \substr($textStream->text, $textStream->position, 4);
        $ch = \mb_substr($substr, 0, 1, 'UTF-8');
        $textStream->position += \strlen($ch);

        return $ch;
    }

    /**
     * Consumes a ident-like token at the current position.
     *
     * @param TextStream $textStream The text stream to consume the ident-like token from.
     *
     * @return AbstractToken The token that was consumed.
     */
    public function consumeIdentLikeToken(TextStream $textStream): AbstractToken
    {
        $tokenOffset = $textStream->position;
        $ident = $this->consumeIdentSequence($textStream);
        \assert($ident !== '');

        if (\strtolower($ident) === 'url' && $textStream->peek(1) === '(') {
            $textStream->position++;
            $textStream->skipWhitespace();
            $ch = $textStream->peek(1);
            if ($ch === '\'' || $ch === '"') {
                return new FunctionToken('url', $tokenOffset, 4);
            }

            return $this->consumeUrlToken($textStream, $tokenOffset);
        }

        if ($textStream->peek(1) === '(') {
            $textStream->position++;

            return new FunctionToken($ident, $tokenOffset, $textStream->position - $tokenOffset);
        }

        return new IdentToken($ident, $tokenOffset, $textStream->position - $tokenOffset);
    }

    /**
     * Consumes an ident sequence at the current position, and returns the unescaped version.
     *
     * @param TextStream $textStream The text stream to consume the ident sequence from.
     *
     * @return string The unescaped ident sequence.
     */
    public function consumeIdentSequence(TextStream $textStream): string
    {
        $name = '';

        while (true) {
            $regexResult = $textStream->matchRegex($this->identCodePointRegex);
            if ($regexResult->success) {
                $name .= $regexResult->value;
                $textStream->position += \strlen($regexResult->value);
            } else {
                $regexResult = $textStream->matchRegex($this->isValidEscRegex);
                if (!$regexResult->success) {
                    break;
                }

                $textStream->position++;
                $name .= $this->consumeEscapedCodePoint($textStream);
            }
        }

        \assert($name !== '');

        return $name;
    }

    /**
     * Consumes a string token. The starting quote character is consumed.
     *
     * @param TextStream $textStream The text stream to consume the string token from.
     * @param string     $ending     The expected ending quote character.
     *
     * @return StringToken|BadStringToken The string token.
     */
    public function consumeStringToken(TextStream $textStream, string $ending): StringToken|BadStringToken
    {
        $tokenOffset = $textStream->position - 1;
        \assert($ending === '"' || $ending === "'");
        $regex = $ending === '"' ? '/["\\n\\\\]/' : "/['\\n\\\\]/";

        $string = '';
        while (true) {
            $regexResult = $textStream->matchRegex($regex, true);
            if (!$regexResult->success) {
                $string .= \substr($textStream->text, $textStream->position);
                $textStream->position = $textStream->length;
                $textStream->recordParseException('Unterminated string.');

                break;
            }

            $pos = $regexResult->offset;
            $string .= \substr($textStream->text, $textStream->position, $pos - $textStream->position);
            $ch = $regexResult->value;

            if ($ch === $ending) {
                $textStream->position = $pos + 1;

                break;
            }

            if ($ch === "\n") {
                $textStream->position = $pos;
                $textStream->recordParseException('Newline found in the string.');

                return new BadStringToken($tokenOffset, $textStream->position - $tokenOffset);
            }

            \assert($ch === '\\');
            $textStream->position = $pos + 1;
            $ch = $textStream->peek(1);
            if ($ch === '') {
                $textStream->recordParseException('Unterminated string.');

                break;
            }

            if ($ch === "\n") {
                $textStream->position++;
            } else {
                $string .= $this->consumeEscapedCodePoint($textStream);
            }
        }

        return new StringToken($string, $tokenOffset, $textStream->position - $tokenOffset);
    }

    /**
     * Consumes a URL token at the current position.
     * This assumes the "url(" has already been consumed.
     *
     * @param TextStream $textStream  The text stream to consume the URL token from.
     * @param int        $tokenOffset The offset of the URL token.
     *
     * @return UrlToken|BadUrlToken The URL token that was consumed.
     */
    public function consumeUrlToken(TextStream $textStream, int $tokenOffset): UrlToken|BadUrlToken
    {
        $textStream->skipWhitespace();
        $url = '';
        while (true) {
            $regexResult = $textStream->matchRegex($this->urlTokenStopRegex, true);
            if (!$regexResult->success) {
                break;
            }

            $ch = $regexResult->value;
            $url .= \substr($textStream->text, $textStream->position, $regexResult->offset - $textStream->position);
            $textStream->position = $regexResult->offset + \strlen($ch);

            if ($ch === ')') {
                return new UrlToken($url, $tokenOffset, $textStream->position - $tokenOffset);
            }

            if ($ch === ' ' || $ch === "\t" || $ch === "\n") {
                $textStream->skipWhitespace();
                if ($textStream->peek(1) === ')') {
                    $textStream->position++;

                    return new UrlToken($url, $tokenOffset, $textStream->position - $tokenOffset);
                }

                $this->consumeRemnantsOfBadUrl($textStream);

                return new BadUrlToken($tokenOffset, $textStream->position - $tokenOffset);
            }

            if ($ch === '\\') {
                if ($textStream->peek(1) === "\n") {
                    $textStream->recordParseException('Unexpected newline in URL.');
                    $this->consumeRemnantsOfBadUrl($textStream);

                    return new BadUrlToken($tokenOffset, $textStream->position - $tokenOffset);
                }

                $url .= $this->consumeEscapedCodePoint($textStream);

                continue;
            }

            $textStream->position = $regexResult->offset;
            if ($ch === '"' || $ch === '\'' || $ch === '(') {
                $ch = \sprintf('"%s"', $ch);
            } else {
                // non-printable character case
                $ch = \sprintf('U+%s', \strtoupper(\dechex(\ord($ch))));
            }
            $textStream->recordParseException(\sprintf('Invalid character %s in URL.', $ch));
            $this->consumeRemnantsOfBadUrl($textStream);

            return new BadUrlToken($tokenOffset, $textStream->position - $tokenOffset);
        }

        // eof case
        $url .= \substr($textStream->text, $textStream->position);
        $textStream->position = $textStream->length;
        $textStream->recordParseException();

        return new UrlToken($url, $tokenOffset, $textStream->position - $tokenOffset);
    }

    /**
     * Converts a text stream to a token stream.
     *
     * @param TextStream $textStream          The text stream to convert.
     * @param bool       $unicodeRangeAllowed Whether unicode-range tokens are allowed.
     *
     * @return TokenStream The token stream.
     */
    public function convertToTokenStream(TextStream $textStream, bool $unicodeRangeAllowed): TokenStream
    {
        /** @var array<int,AbstractToken> $tokens */
        $tokens = [];
        while ($textStream->hasMore()) {
            $this->consumeComments($textStream);
            if (!$textStream->hasMore()) {
                break;
            }
            $tokens[] = $this->convertToToken($textStream, $unicodeRangeAllowed);
        }

        return new TokenStream($tokens, $textStream->errors);
    }

    /**
     * Consumes a number token at the current position, if possible.
     *
     * @param TextStream $textStream The text stream to consume the number token from.
     *
     * @return NumberToken|null The number token, if one was consumed; otherwise, null.
     */
    public function tryConsumeNumberToken(TextStream $textStream): ?NumberToken
    {
        $regexResult = $textStream->matchRegex('/\G[+-]?(\d*\\.)?\d+([Ee][+-]?\d+)?/');
        if (!$regexResult->success) {
            return null;
        }

        $offset = $textStream->position;
        $value = $regexResult->value;
        $textStream->position += \strlen($value);
        $isInt = \preg_match('/[\\.Ee]/', $value) !== 1;
        $hasSign = $value[0] === '+' || $value[0] === '-';
        $value = $isInt ? \intval($value) : \floatval($value);

        return new NumberToken($value, $isInt, $hasSign, $offset, $textStream->position - $offset);
    }

    /**
     * Consumes a numeric token at the current position, if possible.
     *
     * @param TextStream $textStream The text stream to consume the numeric token from.
     *
     * @return AbstractNumericToken|null The numeric token, if one was consumed; otherwise, null.
     */
    public function tryConsumeNumericToken(TextStream $textStream): ?AbstractNumericToken
    {
        $number = $this->tryConsumeNumberToken($textStream);
        if ($number === null) {
            return null;
        }
        $regexResult = $textStream->matchRegex($this->canStartIdentSeqRegex);
        if ($regexResult->success) {
            $unit = $this->consumeIdentSequence($textStream);

            return new DimensionToken(
                $number->value,
                $unit,
                $number->isInt,
                $number->hasSign,
                $number->offset,
                $textStream->position - $number->offset
            );
        }

        if ($textStream->peek(1) === '%') {
            $textStream->position++;

            return new PercentageToken(
                $number->value,
                $number->isInt,
                $number->hasSign,
                $number->offset,
                $textStream->position - $number->offset
            );
        }

        return $number;
    }

    /**
     * Consumes a hash token at the current position, if possible.
     * This assumes the hash sign "#" has already been consumed.
     *
     * @param TextStream $textStream The text stream to consume the hash token from.
     *
     * @return HashToken|null The hash token, if one was consumed; otherwise, null.
     */
    public function tryConsumeHashToken(TextStream $textStream): ?HashToken
    {
        $regexResult = $textStream->matchRegex($this->canStartHashRegex);
        if (!$regexResult->success) {
            return null;
        }

        $tokenOffset = $textStream->position - 1;
        $regexResult = $textStream->matchRegex($this->canStartIdentSeqRegex);
        $isIdType = $regexResult->success;
        $ident = $this->consumeIdentSequence($textStream);

        return new HashToken($ident, $isIdType, $tokenOffset, $textStream->position - $tokenOffset);
    }

    /**
     * Consumes a symbol token at the current position, if possible.
     *
     * @param TextStream $textStream The text stream to consume the symbol token from.
     *
     * @return SymbolToken|null The symbol token, if one was consumed; otherwise, null.
     */
    public function tryConsumeSymbolToken(TextStream $textStream): ?SymbolToken
    {
        $tokenOffset = $textStream->position;
        $ch = $textStream->consume();

        $symbol = match ($ch) {
            ',' => new SymbolToken(Symbol::Comma, $tokenOffset, 1),
            ':' => new SymbolToken(Symbol::Colon, $tokenOffset, 1),
            ';' => new SymbolToken(Symbol::Semicolon, $tokenOffset, 1),
            '(' => new SymbolToken(Symbol::LeftParenthesis, $tokenOffset, 1),
            ')' => new SymbolToken(Symbol::RightParenthesis, $tokenOffset, 1),
            '[' => new SymbolToken(Symbol::LeftSquareBracket, $tokenOffset, 1),
            ']' => new SymbolToken(Symbol::RightSquareBracket, $tokenOffset, 1),
            '{' => new SymbolToken(Symbol::LeftCurlyBracket, $tokenOffset, 1),
            '}' => new SymbolToken(Symbol::RightCurlyBracket, $tokenOffset, 1),
            '-' => $textStream->peek(2) === '->' ? new SymbolToken(Symbol::Cdc, $tokenOffset, 3) : null,
            '<' => $textStream->peek(3) === '!--' ? new SymbolToken(Symbol::Cdo, $tokenOffset, 4) : null,
            default => null,
        };
        if ($symbol !== null) {
            $textStream->position += match ($symbol->value) {
                Symbol::Cdc => 2,
                Symbol::Cdo => 3,
                default => 0,
            };

            return $symbol;
        }

        $textStream->position = $tokenOffset;

        return null;
    }

    /**
     * Consumes a unicode-range token at the current position, if possible.
     *
     * @param TextStream $textStream The text stream to consume the unicode-range token from.
     *
     * @return UnicodeRangeToken|null The unicode-range token.
     */
    public function tryConsumeUnicodeRangeToken(TextStream $textStream): ?UnicodeRangeToken
    {
        $regexResult = $textStream->matchRegex('/\G[Uu]\\+[\\dA-Fa-f]{0,6}\\?{0,6}/');
        if (!$regexResult->success) {
            return null;
        }
        $tokenOffset = $textStream->position;
        $firstSegment = \substr($regexResult->value, 2, 6);
        $textStream->position += 2 + \strlen($firstSegment);
        if (\strpos($firstSegment, '?') !== false) {
            $start = \hexdec(\str_replace('?', '0', $firstSegment));
            \assert(\is_int($start));
            $end = \hexdec(\str_replace('?', 'F', $firstSegment));
            \assert(\is_int($end));

            return new UnicodeRangeToken($start, $end, $tokenOffset, $textStream->position - $tokenOffset);
        }

        $start = \hexdec($firstSegment);
        \assert(\is_int($start));

        $regexResult = $textStream->matchRegex('/\G-[\\dA-Fa-f]{1,6}/');
        if (!$regexResult->success) {
            return new UnicodeRangeToken($start, $start, $tokenOffset, $textStream->position - $tokenOffset);
        }

        $textStream->position += \strlen($regexResult->value);
        $secondSegment = \substr($regexResult->value, 1);
        $end = \hexdec($secondSegment);
        \assert(\is_int($end));

        return new UnicodeRangeToken($start, $end, $tokenOffset, $textStream->position - $tokenOffset);
    }

    /**
     * Consumes a whitespace token at the current position, if possible.
     *
     * @param TextStream $textStream The text stream to consume the whitespace token from.
     *
     * @return WhitespaceToken|null The whitespace token, if one was consumed; otherwise, null.
     */
    public function tryConsumeWhitespaceToken(TextStream $textStream): ?WhitespaceToken
    {
        $regexResult = $textStream->matchRegex('/\G[ \\t\\n]+/');
        if ($regexResult->success) {
            $offset = $textStream->position;
            $length = \strlen($regexResult->value);
            $textStream->position += $length;

            return new WhitespaceToken($offset, $length);
        }

        return null;
    }

    /**
     * Consumes the remnants of a bad URL.
     *
     * @param TextStream $textStream The text stream to consume the bad URL.
     */
    protected function consumeRemnantsOfBadUrl(TextStream $textStream): void
    {
        $regexMatch = $textStream->matchRegex('/(?<!\\\\)\\)/', true);
        $pos = $regexMatch->success ? $regexMatch->offset + 1 : $textStream->length;
        $textStream->position = $pos;
    }

    /**
     * Converts the current position of a text stream to a token.
     *
     * @param TextStream $textStream          The text stream to convert.
     * @param bool       $unicodeRangeAllowed Whether unicode-range tokens are allowed.
     *
     * @return AbstractToken The token at the current position.
     */
    protected function convertToToken(TextStream $textStream, bool $unicodeRangeAllowed): AbstractToken
    {
        $whitespace = $this->tryConsumeWhitespaceToken($textStream);
        if ($whitespace !== null) {
            return $whitespace;
        }

        $symbol = $this->tryConsumeSymbolToken($textStream);
        if ($symbol !== null) {
            return $symbol;
        }

        $ch = $textStream->peek(1);
        $offset = $textStream->position;

        if (\strpos('01234567890+-.', $ch) !== false) {
            $numeric = $this->tryConsumeNumericToken($textStream);
            if ($numeric !== null) {
                return $numeric;
            }
        }
        if ($unicodeRangeAllowed && ($ch === 'U' || $ch === 'u')) {
            $unicodeRange = $this->tryConsumeUnicodeRangeToken($textStream);
            if ($unicodeRange !== null) {
                return $unicodeRange;
            }
        }

        $textStream->position++;

        if ($ch === '+' || $ch === '.' || $ch === '<') {
            return new DelimToken($ch, $offset, 1);
        }
        if ($ch === '"' || $ch === "'") {
            return $this->consumeStringToken($textStream, $ch);
        }
        if ($ch === '#') {
            return $this->tryConsumeHashToken($textStream) ?? new DelimToken($ch, $offset, 1);
        }
        if ($ch === '-') {
            $textStream->position = $offset;
            $regexResult = $textStream->matchRegex($this->canStartIdentSeqRegex);
            if ($regexResult->success) {
                return $this->consumeIdentLikeToken($textStream);
            }
            $textStream->position++;

            return new DelimToken($ch, $offset, 1);
        }

        if ($ch === '@') {
            $regexResult = $textStream->matchRegex($this->canStartIdentSeqRegex);
            if ($regexResult->success) {
                $ident = $this->consumeIdentSequence($textStream);

                return new AtKeywordToken($ident, $offset, $textStream->position - $offset);
            }

            return new DelimToken($ch, $offset, 1);
        }
        if ($ch === '\\') {
            if ($textStream->peek(1) === "\n") {
                $textStream->recordParseException('Unexpected newline.');

                return new DelimToken($ch, $offset, 1);
            }
            $textStream->position = $offset;

            return $this->consumeIdentLikeToken($textStream);
        }

        $regexResult = RegexResult::matches($this->identStartCodePointRegex, $ch);
        if ($regexResult->success) {
            $textStream->position = $offset;

            return $this->consumeIdentLikeToken($textStream);
        }

        return new DelimToken($ch, $offset, 1);
    }
}
