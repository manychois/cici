<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Parsing;

use Manychois\Cici\Exceptions\ParseException;
use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Parsing\SelectorParser;
use Manychois\Cici\Selectors\AbstractSelector;
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

    public static function provideParseAttributeSelector_invalid(): \Generator
    {
        yield ['[', 'Missing attribute name.'];
        yield ['[name', 'Attribute selector is not closed.'];
        yield ['[name[]]', 'Invalid attribute matcher.'];
        yield ['[name?"value"]', 'Invalid attribute matcher.'];
        yield ['[name^"value"]', 'Invalid attribute matcher.'];
        yield ['[name=]', 'Missing attribute value.'];
        yield ['[name=a t]', 'Invalid attribute modifier.'];
        yield ['[name=a i j]', 'Invalid attribute selector.'];
    }

    public static function provideTryParseSubclassSelector(): \Generator
    {
        yield ['#abc', '{"id":"abc","type":"id"}', 1];
        yield ['.abc', '{"className":"abc","type":"class"}', 2];
        yield ['[name]', '{"matcher":"","type":"attribute","wqName":{"localName":"name"}}', 3];
        yield [':first-child', '{"name":"first-child","type":"pseudo-class"}', 2];
        yield ['!important', 'null', 0];
    }

    public static function provideTryParseSubclassSelector_invalid(): \Generator
    {
        yield ['#123', 'Invalid ID selector.'];
        yield ['.!', 'Invalid class selector.'];
    }

    public static function provideTryParseTypeSelector(): \Generator
    {
        yield ['div', '{"type":"type","wqName":{"localName":"div"}}', 1];
        yield ['*', '{"type":"type","wqName":{"localName":"*"}}', 1];
        yield ['|div', '{"type":"type","wqName":{"localName":"div","prefix":null}}', 2];
        yield ['*|div', '{"type":"type","wqName":{"localName":"div","prefix":"*"}}', 3];
        yield ['svg|title', '{"type":"type","wqName":{"localName":"title","prefix":"svg"}}', 3];
        yield ['*|*', '{"type":"type","wqName":{"localName":"*","prefix":"*"}}', 3];
        yield ['123', 'null', 0];
    }

    public static function provideTryParsePseudoCompoundSelector(): \Generator
    {
        yield [
            '::file-selector-button:hover',
            '{"selectors":[' . '{"name":"file-selector-button","type":"pseudo-element"},' .
                '{"name":"hover","type":"pseudo-class"}],"type":"and"}',
            5,
        ];

        yield [':before', '{"name":"before","type":"legacy-pseudo-element"}', 2];
        yield [':123', 'null', 0];
        yield ['div', 'null', 0];
        yield ['::after:123', '{"name":"after","type":"pseudo-element"}', 3];
        yield ['::after!', '{"name":"after","type":"pseudo-element"}', 3];
    }

    public static function provideTryParseCompoundSelector(): \Generator
    {
        yield [
            'div.active:hover',
            '{"selectors":[{"type":"type","wqName":{"localName":"div"}},' .
                '{"className":"active","type":"class"},' .
                '{"name":"hover","type":"pseudo-class"}],"type":"compound"}',
            5,
        ];

        yield ['::before', 'null', 0];
    }

    public static function provideTryParseComplexSelector(): \Generator
    {
        $c = static fn ($s): string => '{"selectors":[' . $s . '],"type":"compound"}';
        $and = static fn ($s): string => '{"selectors":[' . $s . '],"type":"and"}';

        yield [
            'div > :hover [required]',
            false,
            '{"combinators":[">"," "],"selectors":[' .
                $c('{"type":"type","wqName":{"localName":"div"}}') . ',' .
                $c('{"name":"hover","type":"pseudo-class"}') . ',' .
                $c('{"matcher":"","type":"attribute","wqName":{"localName":"required"}}') .
                '],"type":"complex"}',
            10,
        ];

        yield [
            'p+::first-letter:hover',
            false,
            '{"combinators":["+"],"selectors":[' .
                $c('{"type":"type","wqName":{"localName":"p"}}') . ',' .
                $and('{"name":"first-letter","type":"pseudo-element"},{"name":"hover","type":"pseudo-class"}') .
                '],"type":"complex"}',
            7,
        ];

        yield [
            'div.active',
            false,
            $c('{"type":"type","wqName":{"localName":"div"}},{"className":"active","type":"class"}'),
            3,
        ];

        yield [
            'main  ~  ::selection::first-letter',
            false,
            '{"combinators":["~"],"selectors":[' .
                $c('{"type":"type","wqName":{"localName":"main"}}') . ',' .
                $and('{"name":"selection","type":"pseudo-element"},{"name":"first-letter","type":"pseudo-element"}') .
                '],"type":"complex"}',
            10,
        ];

        yield [
            'a || ?',
            false,
            $c('{"type":"type","wqName":{"localName":"a"}}'),
            1,
        ];

        yield [
            'a || b::first-line',
            true,
            '{"combinators":["||"],"selectors":[' .
                $c('{"type":"type","wqName":{"localName":"a"}}') . ',' .
                $c('{"type":"type","wqName":{"localName":"b"}}') .
                '],"type":"complex"}',
            6,
        ];

        yield ['!', false, 'null', 0];
        yield ['a ! b', false, $c('{"type":"type","wqName":{"localName":"a"}}'), 1];
    }

    public static function provideTryParseCommaSeparatedList(): \Generator
    {
        yield [
            'a, [href] ,.link',
            '{"selectors":[' .
                '{"selectors":[{"type":"type","wqName":{"localName":"a"}}],"type":"compound"},' .
                '{"selectors":[{"matcher":"","type":"attribute","wqName":{"localName":"href"}}],"type":"compound"},' .
                '{"selectors":[{"className":"link","type":"class"}],"type":"compound"}' .
                '],"type":"or"}',
            10,
        ];

        yield ['!', 'null', 0];

        yield ['#id-1', '{"selectors":[{"id":"id-1","type":"id"}],"type":"compound"}', 1];
    }

    public static function provideParseSelectorListInvalidInput(): \Generator
    {
        yield ['!a,b,c'];
        yield ['a,b,c!'];
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
        self::assertSame($expectedJson, Json::encode($wqName));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParsePseudoElementSelector')]
    public function testTryParsePseudoElementSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first colon
        $tokenStream->tryConsume();
        $pseudoElement = $this->parser->tryParsePseudoElementSelector($tokenStream);
        self::assertSame($expectedJson, Json::encode($pseudoElement));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideParseAttributeSelector')]
    public function testParseAttributeSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first left square bracket
        $tokenStream->tryConsume();
        $attributeSelector = $this->parser->parseAttributeSelector($tokenStream);
        self::assertSame($expectedJson, Json::encode($attributeSelector));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideParseAttributeSelector_invalid')]
    public function testParseAttributeSelector_invalid(string $input, string $expectedEx): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedEx);
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first left square bracket
        $tokenStream->tryConsume();
        $this->parser->parseAttributeSelector($tokenStream);
    }

    #[DataProvider('provideTryParseSubclassSelector')]
    public function testTryParseSubclassSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        $subclassSelector = $this->parser->tryParseSubclassSelector($tokenStream);
        self::assertSame($expectedJson, Json::encode($subclassSelector));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParseSubclassSelector_invalid')]
    public function testTryParseSubclassSelector_invalid(string $input, string $expectedError): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedError);
        $tokenStream = $this->convertToTokenStream($input);
        $this->parser->tryParseSubclassSelector($tokenStream);
    }

    #[DataProvider('provideTryParseTypeSelector')]
    public function testTryParseTypeSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        $typeSelector = $this->parser->tryParseTypeSelector($tokenStream);
        self::assertSame($expectedJson, Json::encode($typeSelector));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParsePseudoCompoundSelector')]
    public function testTryParsePseudoCompoundSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        $typeSelector = $this->parser->tryParsePseudoCompoundSelector($tokenStream);
        self::assertSame($expectedJson, Json::encode($typeSelector));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParseCompoundSelector')]
    public function testTryParseCompoundSelector(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        $typeSelector = $this->parser->tryParseCompoundSelector($tokenStream, false);
        self::assertSame($expectedJson, Json::encode($typeSelector));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParseComplexSelector')]
    public function testTryParseComplexSelector(string $input, bool $real, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        $typeSelector = $this->parser->tryParseComplexSelector($tokenStream, $real, false);
        self::assertSame($expectedJson, Json::encode($typeSelector));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    #[DataProvider('provideTryParseCommaSeparatedList')]
    public function testTryParseCommaSeparatedList(string $input, string $expectedJson, int $indexAfter): void
    {
        $tokenStream = $this->convertToTokenStream($input);
        $parseComplexSelector = fn (): ?AbstractSelector => $this->parser->tryParseComplexSelector(
            $tokenStream,
            false,
            false
        );
        $typeSelector = $this->parser->tryParseCommaSeparatedList($tokenStream, $parseComplexSelector);
        self::assertSame($expectedJson, Json::encode($typeSelector));
        self::assertSame($indexAfter, $tokenStream->position);
    }

    public function testParseSelectorList(): void
    {
        $input = ' a, .b, #c ';
        $tokenStream = $this->convertToTokenStream($input);
        $selectorList = $this->parser->parseSelectorList($tokenStream);
        self::assertSame(
            '{"selectors":[{"selectors":[{"type":"type","wqName":{"localName":"a"}}],"type":"compound"},' .
                '{"selectors":[{"className":"b","type":"class"}],"type":"compound"},' .
                '{"selectors":[{"id":"c","type":"id"}],"type":"compound"}],"type":"or"}',
            Json::encode($selectorList)
        );
    }

    #[DataProvider('provideParseSelectorListInvalidInput')]
    public function testParseSelectorListInvalidInput(string $input): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid selector list.');
        $tokenStream = $this->convertToTokenStream($input);
        $this->parser->parseSelectorList($tokenStream);
    }

    #region extends TestCase

    #[\Override]
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
