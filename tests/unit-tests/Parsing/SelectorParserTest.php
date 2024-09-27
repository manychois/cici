<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Parsing;

use Manychois\Cici\Exceptions\ParseException;
use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Parsing\SelectorParser;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;
use Manychois\Cici\Tokenization\TokenStream;
use Manychois\Cici\Utilities\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SelectorParserTest extends TestCase
{
    private SelectorParser $parser;
    private ParseExceptionCollection $errors;

    public static function provideTryParseWqName(): \Generator
    {
        yield ['*|*', true, '{"localName":"*","prefix":"*"}', 3];
        yield ['*|*', false, 'null', 0];
        yield ['svg|*', true, '{"localName":"*","prefix":"svg"}', 3];
        yield ['svg|*', false, '{"localName":"svg","prefix":null}', 1];
        yield ['|div', true, '{"localName":"div","prefix":null}', 2];
        yield ['|*', true, '{"localName":"*","prefix":null}', 2];
        yield ['|*', false, 'null', 0];
        yield ['div', true, '{"localName":"div"}', 1];
        yield ['*', true, '{"localName":"*"}', 1];
        yield ['*', false, 'null', 0];
        yield ['', true, 'null', 0];
    }

    public static function provideTryParsePseudoElementSelector(): \Generator
    {
        yield ['::before', '{"name":"before","type":"pseudo-element"}', 3];
        yield [':before', '{"name":"before","type":"legacy-pseudo-element"}', 2];
        yield [':first-child', 'null', 1];
    }

    public static function provideTryParsePseudoClassSelector(): \Generator
    {
        $args = \implode(',', [
            '{"hasSign":false,"isInt":true,"type":"dimension","unit":"n","value":2}',
            '{"hasSign":true,"isInt":true,"type":"number","value":1}',
            '" "',
            '{"type":"ident","value":"of"}',
            '" "',
            '{"type":"delim","value":"."}',
            '{"type":"ident","value":"active"}',
        ]);

        yield [':unknown(2n+1 of .active)', \sprintf(
            '{"args":[%s],"name":"unknown","type":"pseudo-class"}',
            $args
        ), 10, ];

        yield [':"bad token"', 'null', 1];
    }

    public static function provideTryParsePseudoClassSelector_funcNotClosed(): \Generator
    {
        yield [':first-child(2n+1', 4];
        yield [':unknown("' . "\n" . '")', 2];
    }

    public static function provideParseAttributeSelector(): \Generator
    {
        yield [
            '[required]',
            '{"matcher":"","type":"attribute","wqName":{"localName":"required"}}',
            3,
        ];

        yield [
            '[|type=text i]',
            '{"isCaseSensitive":false,"matcher":"=","type":"attribute","value":"text",' .
            '"wqName":{"localName":"type","prefix":null}}',
            8,
        ];

        yield [
            "[data-target ~='tooltip']",
            '{"matcher":"~=","type":"attribute","value":"tooltip","wqName":{"localName":"data-target"}}',
            7,
        ];

        yield [
            '[*|lang|= en s]',
            '{"isCaseSensitive":true,"matcher":"|=","type":"attribute","value":"en",' .
            '"wqName":{"localName":"lang","prefix":"*"}}',
            11,
        ];

        yield [
            '[ href ^= "https://" ]',
            '{"matcher":"^=","type":"attribute","value":"https://","wqName":{"localName":"href"}}',
            10,
        ];

        yield [
            '[ns|href $=".pdf"]',
            '{"matcher":"$=","type":"attribute","value":".pdf","wqName":{"localName":"href","prefix":"ns"}}',
            9,
        ];

        yield [
            '[data-label*=example ]',
            '{"matcher":"*=","type":"attribute","value":"example","wqName":{"localName":"data-label"}}',
            7,
        ];
    }

    #[DataProvider('provideTryParseWqName')]
    public function testTryParseWqName(
        string $input,
        bool $allowWildcastLocalName,
        string $expectedJson,
        int $indexAfter
    ): void {
        $tokenStream = $this->convertToTokenStream($input);
        $wqName = $this->parser->tryParseWqName($tokenStream, $allowWildcastLocalName);
        $this->assertSame($expectedJson, Json::encode($wqName));
        $this->assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParsePseudoElementSelector')]
    public function testTryParsePseudoElementSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first colon
        $tokenStream->tryConsume();
        $pseudoElement = $this->parser->tryParsePseudoElementSelector($tokenStream);
        $this->assertSame($expectedJson, Json::encode($pseudoElement));
        $this->assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParsePseudoClassSelector')]
    public function testTryParsePseudoClassSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first colon
        $tokenStream->tryConsume();
        $pseudoClass = $this->parser->tryParsePseudoClassSelector($tokenStream);
        $this->assertSame($expectedJson, Json::encode($pseudoClass));
        $this->assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParsePseudoClassSelector_funcNotClosed')]
    public function testTryParsePseudoClassSelector_funcNotClosed(string $input, int $indexAfter): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('The function is not closed.');
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first colon
        $tokenStream->tryConsume();
        $this->parser->tryParsePseudoClassSelector($tokenStream);
        $this->assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideParseAttributeSelector')]
    public function testParseAttributeSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first left square bracket
        $tokenStream->tryConsume();
        $attributeSelector = $this->parser->parseAttributeSelector($tokenStream);
        $this->assertSame($expectedJson, Json::encode($attributeSelector));
        $this->assertSame($indexAfter, $tokenStream->position);
    }

    #region extends TestCase

    protected function setUp(): void
    {
        $this->parser = new SelectorParser();
        $this->errors = new ParseExceptionCollection();
    }

    #endregion extends TestCase

    private function convertToTokenStream(string $input): TokenStream
    {
        $tokenizer = new Tokenizer();
        $textStream = new TextStream($input, $this->errors);

        return $tokenizer->convertToTokenStream($textStream, false);
    }
}
