<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Tokenization\Tokens;

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public static function provideEscapeIdent(): \Generator
    {
        yield ['foo', 'foo'];
        yield ['ID-1', 'ID-1'];
        yield ['123', '\31 23'];
        yield ['--ok', '--ok'];
        yield ['-minus', '-minus'];
        yield ['_underscore', '_underscore'];
        yield ['\backslash', '\5C backslash'];
        yield ['中文', '中文'];
    }

    public static function provideToString(): \Generator
    {
        yield [new AtKeywordToken('foo', 0, 4), '@foo'];
        yield [new BadStringToken(0, 12), '"bad-string"'];
        yield [new BadUrlToken(0, 12), 'url(bad-url)'];
        yield [new DelimToken('>', 0, 1), '>'];
        yield [new DimensionToken(1.23, 'px', false, false, 0, 6), '1.23px'];
        yield [new FunctionToken('last-child', 0, 11), 'last-child('];
        yield [new HashToken('123', false, 0, 4), '#123'];
        yield [new IdentToken('foo', 0, 3), 'foo'];
        yield [new NumberToken(-2, true, true, 0, 2), '-2'];
        yield [new PercentageToken(50, true, true, 0, 3), '50%'];
        yield [new StringToken("'line1\nline2\"", 0, 20), '"\'line1\\A line2\\22 "'];
        yield [new SymbolToken(Symbol::Colon, 0, 1), ':'];
        yield [new UnicodeRangeToken(0, 64, 0, 6), 'U+0-40'];
        yield [new UnicodeRangeToken(123, 123, 0, 4), 'U+7B'];
        yield [new UrlToken("'line1\n(line2)\"", 0, 34), 'url(\\27 line1\\A \\28 line2\\29 \\22 )'];
        yield [new WhitespaceToken(0, 1), ' '];
    }

    public function testEscape(): void
    {
        $input = 'a b';
        $escaped = AbstractToken::escape($input, '/\s/');
        $this->assertSame('a\20 b', $escaped);

        $input = "a\u{10FFFF}b";
        $escaped = AbstractToken::escape($input, '/[^a-z]/u');
        $this->assertSame('a\10FFFFb', $escaped);
    }

    public function testEscape_invalidCharacter(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The character is not a valid UTF-8 character.');

        AbstractToken::escape("\xFF", '/./');
    }

    #[DataProvider('provideEscapeIdent')]
    public function testEscapeIdent(string $from, string $to): void
    {
        $this->assertSame($to, AbstractToken::escapeIdent($from));
    }

    #[DataProvider('provideToString')]
    public function testToString(AbstractToken $token, string $expected): void
    {
        $this->assertSame($expected, $token->__toString());
    }
}
