<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Tokenization;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;
use Manychois\Cici\Utilities\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TokenizerTest extends TestCase
{
    private ParseExceptionCollection $errors;

    public static function provideConsumeComments(): \Generator
    {
        yield ['/* comment */abc', 13];
        yield ['a /* comment */ b', 0];
        yield ['/* a *//* b */', 14];
    }

    public static function provideTryConsumeNumberToken(): \Generator
    {
        yield ['123', '{"hasSign":false,"isInt":true,"type":"number","value":123}', 3];
        yield ['+234.567', '{"hasSign":true,"isInt":false,"type":"number","value":234.567}', 8];
        yield ['-567e2', '{"hasSign":true,"isInt":false,"type":"number","value":-56700}', 6];
        yield ['+8.91E-3', '{"hasSign":true,"isInt":false,"type":"number","value":0.00891}', 8];
        yield ['a', 'null', 0];
    }

    public static function provideConsumeEscapedCodePoint(): \Generator
    {
        yield ['41 B', 'A', 3];
        yield ['?123', '?', 1];
        yield ['中文', '中', 3];
    }

    public static function provideConsumeEscapedCodePoint_invalidCodePoint(): \Generator
    {
        yield ['0 123', 'Invalid unicode code point U+0.', 2];
        yield ['1234567', 'Invalid unicode code point U+123456.', 6];
        yield ['', 'Unexpected end of input.', 0];
    }

    public static function provideConsumeIdentSequence(): \Generator
    {
        yield ['ABCdef123', 'ABCdef123', 9];
        yield ['-123.456', '-123', 4];
        yield ['--\41 \42 \43 DEF', '--ABCDEF', 17];
    }

    public static function provideTryConsumeNumericToken(): \Generator
    {
        yield ['abc', 'null', 0];
        yield ['3.14', '{"hasSign":false,"isInt":false,"type":"number","value":3.14}', 4];
        yield ['1rem', '{"hasSign":false,"isInt":true,"type":"dimension","unit":"rem","value":1}', 4];
        yield ['-2%', '{"hasSign":true,"isInt":true,"type":"percentage","value":-2}', 3];
    }

    public static function provideConsumeUrlToken(): \Generator
    {
        yield ['url(https://example.test)', '{"type":"url","value":"https://example.test"}', 25];
        yield ['url(https://example.test  )', '{"type":"url","value":"https://example.test"}', 27];
        yield ['url(https://example.test?q=\41&p=2)', '{"type":"url","value":"https://example.test?q=A&p=2"}', 35];
        yield ['url(a\ b)', '{"type":"url","value":"a b"}', 9];
        yield ['url(a b)', '{"type":"bad-url"}', 8];
        yield ['url(a \) b c d e f)', '{"type":"bad-url"}', 19];
    }

    public static function provideConsumeUrlToken_invalidUrl(): \Generator
    {
        yield ['url(abc\)', '{"type":"url","value":"abc)"}', 'Unexpected end of input.', 9];
        yield ["url(line1\\\nline2)", '{"type":"bad-url"}', 'Unexpected newline in URL.', 17];
        yield ['url(abc"def)', '{"type":"bad-url"}', 'Invalid character """ in URL.', 12];
        yield ["url(a\u{7F}b)", '{"type":"bad-url"}', 'Invalid character U+7F in URL.', 8];
    }

    public static function provideConsumeIdentLikeToken(): \Generator
    {
        yield ['abc', '{"type":"ident","value":"abc"}', 3];
        yield ['--123', '{"type":"ident","value":"--123"}', 5];
        yield ['url(  abc.jpg  )', '{"type":"url","value":"abc.jpg"}', 16];
        yield ['url("abc.jpg")', '{"name":"url","type":"function"}', 4];
        yield ['nth-child(3n+1)', '{"name":"nth-child","type":"function"}', 10];
    }

    public static function provideConsumeStringToken(): \Generator
    {
        yield ['"abc"', '{"type":"string","value":"abc"}', 5];
        yield ["'a\\41 b\\42 c\\43\\'d'", '{"type":"string","value":"aAbBcC\'d"}', 19];
        yield ["'line1\\\nline2'", '{"type":"string","value":"line1line2"}', 14];
    }

    public static function provideConsumeStringToken_invalid(): \Generator
    {
        yield ['"abc', '{"type":"string","value":"abc"}', 'Unterminated string.', 4];
        yield ['"abc\\', '{"type":"string","value":"abc"}', 'Unterminated string.', 5];
        yield ["'abc\ndef'", '{"type":"bad-string"}', 'Newline found in the string.', 4];
    }

    public static function provideTryConsumeUnicodeRangeToken(): \Generator
    {
        yield ['U+26', '{"end":38,"start":38,"type":"unicode-range"}', 4];
        yield ['U+0-7F', '{"end":127,"start":0,"type":"unicode-range"}', 6];
        yield ['U+0025-00FF', '{"end":255,"start":37,"type":"unicode-range"}', 11];
        yield ['U+4??', '{"end":1279,"start":1024,"type":"unicode-range"}', 5];
        yield ['ufo', 'null', 0];
    }

    public static function provideTryConsumeHashToken(): \Generator
    {
        yield ['#123', '{"isIdType":false,"type":"hash","value":"123"}', 4];
        yield ['#abc-def', '{"isIdType":true,"type":"hash","value":"abc-def"}', 8];
        yield ['#', 'null', 1];
        yield ['# ', 'null', 1];
    }

    public static function provideTryConsumeSymbolToken(): \Generator
    {
        yield [',', '","', 1];
        yield [':', '":"', 1];
        yield [';', '";"', 1];
        yield ['(', '"("', 1];
        yield [')', '")"', 1];
        yield ['[', '"["', 1];
        yield [']', '"]"', 1];
        yield ['{', '"{"', 1];
        yield ['}', '"}"', 1];
        yield ['-', 'null', 0];
        yield ['-->', '"-->"', 3];
        yield ['<', 'null', 0];
        yield ['<!--', '"<!--"', 4];
        yield ['?', 'null', 0];
    }

    public static function provideTryConsumeWhitespaceToken(): \Generator
    {
        yield [' ', '" "', 1];
        yield [" \t\n ", '" "', 4];
        yield ['\\20', 'null', 0];
    }

    public static function provideConvertToTokenStream(): \Generator
    {
        yield [
            'margin-left: /* comment */ 10px;',
            '[' . \implode(',', [

                '{"type":"ident","value":"margin-left"}',
                '":"',
                '" "',
                '" "',
                '{"hasSign":false,"isInt":true,"type":"dimension","unit":"px","value":10}',
                '";"',
            ]) . ']',
        ];

        yield [
            "@font-face { \n font-family: sans-serif; \n unicode-range: U+0-7F \n }",
            '[' . \implode(',', [

                '{"type":"at-keyword","value":"font-face"}',
                '" "',
                '"{"',
                '" "',
                '{"type":"ident","value":"font-family"}',
                '":"',
                '" "',
                '{"type":"ident","value":"sans-serif"}',
                '";"',
                '" "',
                '{"type":"ident","value":"unicode-range"}',
                '":"',
                '" "',
                '{"end":127,"start":0,"type":"unicode-range"}',
                '" "',
                '"}"',
            ]) . ']',
        ];

        yield [
            'div.a{color:#fff!important;}',
            '[' . \implode(',', [

                '{"type":"ident","value":"div"}',
                '{"type":"delim","value":"."}',
                '{"type":"ident","value":"a"}',
                '"{"',
                '{"type":"ident","value":"color"}',
                '":"',
                '{"isIdType":true,"type":"hash","value":"fff"}',
                '{"type":"delim","value":"!"}',
                '{"type":"ident","value":"important"}',
                '";"',
                '"}"',
            ]) . ']',
        ];

        yield [
            'background:url("https://example.test?q=\41&p=2") no-repeat;',
            '[' . \implode(',', [

                '{"type":"ident","value":"background"}',
                '":"',
                '{"name":"url","type":"function"}',
                '{"type":"string","value":"https://example.test?q=A&p=2"}',
                '")"',
                '" "',
                '{"type":"ident","value":"no-repeat"}',
                '";"',
            ]) . ']',
        ];

        yield [
            '--color:#123',
            '[' . \implode(',', [

                '{"type":"ident","value":"--color"}',
                '":"',
                '{"isIdType":false,"type":"hash","value":"123"}',
            ]) . ']',
        ];

        yield [
            '2n - 1',
            '[' . \implode(',', [

                '{"hasSign":false,"isInt":true,"type":"dimension","unit":"n","value":2}',
                '" "',
                '{"type":"delim","value":"-"}',
                '" "',
                '{"hasSign":false,"isInt":true,"type":"number","value":1}',
            ]) . ']',
        ];

        yield [
            '@ charset \'iso-8859-15\'; /* invalid */',
            '[' . \implode(',', [
                '{"type":"delim","value":"@"}',
                '" "',
                '{"type":"ident","value":"charset"}',
                '" "',
                '{"type":"string","value":"iso-8859-15"}',
                '";"',
                '" "',
            ]) . ']',
        ];

        yield ['\\40-123', '[{"type":"ident","value":"@-123"}]'];
    }

    #[DataProvider('provideConsumeComments')]
    public function testConsumeComments(string $text, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $tokenizer->consumeComments($textStream);
        self::assertSame($expectedPosition, $textStream->position);
    }

    public function testConsumeComments_unclosed(): void
    {
        $textStream = new TextStream('/* not closed', $this->errors);
        $tokenizer = new Tokenizer();
        $tokenizer->consumeComments($textStream);
        self::assertSame(13, $textStream->position);
        self::assertCount(1, $this->errors);
        $error = $this->errors->get(0);
        self::assertSame('Unterminated comment.', $error->getMessage());
        self::assertSame(0, $error->position);
    }

    #[DataProvider('provideTryConsumeNumberToken')]
    public function testTryConsumeNumberToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $number = $tokenizer->tryConsumeNumberToken($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($number));
    }

    #[DataProvider('provideConsumeEscapedCodePoint')]
    public function testConsumeEscapedCodePoint(string $text, string $expectedResult, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $unescaped = $tokenizer->consumeEscapedCodePoint($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedResult, $unescaped);
    }

    #[DataProvider('provideConsumeEscapedCodePoint_invalidCodePoint')]
    public function testConsumeEscapedCodePoint_invalidCodePoint(
        string $text,
        string $expectedErrorMsg,
        int $expectedPosition
    ): void {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $unescaped = $tokenizer->consumeEscapedCodePoint($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame("\u{FFFD}", $unescaped);
        self::assertCount(1, $this->errors);
        $error = $this->errors->get(0);
        self::assertSame($expectedErrorMsg, $error->getMessage());
    }

    #[DataProvider('provideConsumeIdentSequence')]
    public function testConsumeIdentSequence(string $text, string $expectedResult, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $ident = $tokenizer->consumeIdentSequence($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedResult, $ident);
    }

    #[DataProvider('provideTryConsumeNumericToken')]
    public function testTryConsumeNumericToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $numeric = $tokenizer->tryConsumeNumericToken($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($numeric));
    }

    #[DataProvider('provideConsumeUrlToken')]
    public function testConsumeUrlToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $textStream->position = 4;
        $tokenizer = new Tokenizer();
        $numeric = $tokenizer->consumeUrlToken($textStream, 0);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($numeric));
    }

    #[DataProvider('provideConsumeUrlToken_invalidUrl')]
    public function testConsumeUrlToken_invalidUrl(
        string $text,
        string $expectedJson,
        string $expectedErrMsg,
        int $expectedPosition
    ): void {
        $textStream = new TextStream($text, $this->errors);
        $textStream->position = 4;
        $tokenizer = new Tokenizer();
        $numeric = $tokenizer->consumeUrlToken($textStream, 0);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($numeric));
        self::assertCount(1, $this->errors);
        $error = $this->errors->get(0);
        self::assertSame($expectedErrMsg, $error->getMessage());
    }

    #[DataProvider('provideConsumeIdentLikeToken')]
    public function testConsumeIdentLikeToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $token = $tokenizer->consumeIdentLikeToken($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($token));
    }

    #[DataProvider('provideConsumeStringToken')]
    public function testConsumeStringToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $start = $textStream->consume();
        $token = $tokenizer->consumeStringToken($textStream, $start);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($token));
    }

    #[DataProvider('provideConsumeStringToken_invalid')]
    public function testConsumeStringToken_invalid(
        string $text,
        string $expectedJson,
        string $expectedErrMsg,
        int $expectedPosition
    ): void {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $start = $textStream->consume();
        $token = $tokenizer->consumeStringToken($textStream, $start);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($token));
        self::assertCount(1, $this->errors);
        $error = $this->errors->get(0);
        self::assertSame($expectedErrMsg, $error->getMessage());
    }

    #[DataProvider('provideTryConsumeUnicodeRangeToken')]
    public function testTryConsumeUnicodeRangeToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $token = $tokenizer->tryConsumeUnicodeRangeToken($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($token));
    }

    #[DataProvider('provideTryConsumeHashToken')]
    public function testTryConsumeHashToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        // consume #
        $textStream->consume();
        $token = $tokenizer->tryConsumeHashToken($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($token));
    }

    #[DataProvider('provideTryConsumeSymbolToken')]
    public function testTryConsumeSymbolToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $token = $tokenizer->tryConsumeSymbolToken($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($token));
    }

    #[DataProvider('provideTryConsumeWhitespaceToken')]
    public function testTryConsumeWhitespaceToken(string $text, string $expectedJson, int $expectedPosition): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $token = $tokenizer->tryConsumeWhitespaceToken($textStream);
        self::assertSame($expectedPosition, $textStream->position);
        self::assertSame($expectedJson, Json::encode($token));
    }

    #[DataProvider('provideConvertToTokenStream')]
    public function testConvertToTokenStream(string $text, string $expectedJson): void
    {
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $tokenStream = $tokenizer->convertToTokenStream($textStream, true);
        $tokens = [];
        while ($tokenStream->hasMore()) {
            $tokens[] = $tokenStream->tryConsume();
        }
        self::assertSame($expectedJson, Json::encode($tokens));
    }

    public function testConvertToTokenStream_invalidNewline(): void
    {
        $text = "line1\\\nline2";
        $textStream = new TextStream($text, $this->errors);
        $tokenizer = new Tokenizer();
        $tokenStream = $tokenizer->convertToTokenStream($textStream, true);
        $tokens = [];
        while ($tokenStream->hasMore()) {
            $tokens[] = $tokenStream->tryConsume();
        }
        $expectedJson = '[' . \implode(
            ',',
            [
                '{"type":"ident","value":"line1"}',
                '{"type":"delim","value":"\\\\"}',
                '" "',
                '{"type":"ident","value":"line2"}',
            ]
        ) . ']';
        self::assertSame($expectedJson, Json::encode($tokens));
        self::assertCount(1, $this->errors);
        $error = $this->errors->get(0);
        self::assertSame('Unexpected newline.', $error->getMessage());
    }

    #region extends TestCase

    #[\Override]
    protected function setUp(): void
    {
        $this->errors = new ParseExceptionCollection();
    }

    #endregion extends TestCase
}
