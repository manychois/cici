<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Parsing;

use Manychois\Cici\Exceptions\ParseException;
use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Parsing\PseudoSelectorParser;
use Manychois\Cici\Parsing\SelectorParser;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;
use Manychois\Cici\Tokenization\TokenStream;
use Manychois\Cici\Utilities\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PseudoSelectorParserTest extends TestCase
{
    private SelectorParser $mainParser;
    private PseudoSelectorParser $parser;
    private ParseExceptionCollection $errors;

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

        $compound = static fn (...$s) => \sprintf('{"selectors":[%s],"type":"compound"}', \implode(',', $s));
        $complex = static fn ($c, ...$s) => \sprintf(
            '{"combinators":[%s],"selectors":[%s],"type":"complex"}',
            $c,
            \implode(',', $s)
        );
        $relative = static fn ($c, $s) => \sprintf('{"combinator":"%s","selector":%s,"type":"relative"}', $c, $s);
        $or = static fn (...$s) => \sprintf('{"selectors":[%s],"type":"or"}', \implode(',', $s));
        $psSelector = static fn ($n, $s) => \sprintf('{"name":"%s","selector":%s,"type":"pseudo-class"}', $n, $s);

        yield 'has#1' => [
            ':has(img)',
            $psSelector('has', $relative(' ', $compound('{"type":"type","wqName":{"localName":"img"}}'))),
            4,
        ];

        yield 'has#2' => [
            ':has( > p ~ .primary )',
            $psSelector('has', $relative(
                '>',
                $complex(
                    '"~"',
                    $compound('{"type":"type","wqName":{"localName":"p"}}'),
                    $compound('{"className":"primary","type":"class"}')
                )
            )),
            13,
        ];

        yield 'is#1' => [
            ':is(.primary, [error!], .secondary)',
            $psSelector('is', '{"selectors":[' .
                $compound('{"className":"primary","type":"class"}') . ',' .
                $compound('{"className":"secondary","type":"class"}') .
            '],"type":"forgiving-selector-list"}'),
            15,
        ];

        yield 'where#1' => [
            ':where(#123, abc, ,,)',
            $psSelector('where', '{"selectors":[' .
            $compound('{"type":"type","wqName":{"localName":"abc"}}') . '],"type":"forgiving-selector-list"}'),
            11,
        ];

        yield 'not#1' => [
            ':not(:has(a), b)',
            $psSelector(
                'not',
                $or(
                    $compound(
                        $psSelector('has', $relative(' ', $compound('{"type":"type","wqName":{"localName":"a"}}')))
                    ),
                    $compound('{"type":"type","wqName":{"localName":"b"}}')
                )
            ),
            10,
        ];
    }

    public static function provideTryParsePseudoClassSelector_invalid(): \Generator
    {
        yield [':first-child(2n+1', 'The function is not closed.', 4];
        yield [':unknown("' . "\n" . '")', 'The function is not closed.', 2];
        yield [':has(:has(a))', 'The :has() pseudo-class cannot be nested.', 3];
        yield [':has(!a)', 'Invalid argument for the :has() pseudo-class.', 3];
        yield [':has(a!)', 'Unexpected token inside pseudo-class "has".', 4];
        yield [':not({})', 'Invalid argument for the :not() pseudo-class.', 4];
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

    #[DataProvider('provideTryParsePseudoClassSelector_invalid')]
    public function testTryParsePseudoClassSelector_invalid(string $input, string $errMsg, int $indexAfter): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($errMsg);
        $tokenStream = $this->convertToTokenStream($input);
        // Consume the first colon
        $tokenStream->tryConsume();
        $this->parser->tryParsePseudoClassSelector($tokenStream);
        $this->assertSame($indexAfter, $tokenStream->position);
    }


    #region extends TestCase

    protected function setUp(): void
    {
        $this->mainParser = new SelectorParser();
        $this->parser = new PseudoSelectorParser($this->mainParser);
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
