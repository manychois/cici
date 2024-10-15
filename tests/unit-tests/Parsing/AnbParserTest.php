<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Parsing;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Parsing\AnbParser;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;
use Manychois\Cici\Utilities\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnbParserTest extends TestCase
{
    public static function provideTryParse(): \Generator
    {
        yield ['odd', '{"a":2,"b":1,"keyword":"odd"}', 1];
        yield ['even', '{"a":2,"b":0,"keyword":"even"}', 1];

        // <integer>
        yield ['3', '{"a":0,"b":3}', 1];
        yield ['-4', '{"a":0,"b":-4}', 1];

        // <n-dimension>
        yield ['3n', '{"a":3,"b":0}', 1];
        yield ['-3n', '{"a":-3,"b":0}', 1];

        // '+'? n
        yield ['n', '{"a":1,"b":0}', 1];
        yield ['+n', '{"a":1,"b":0}', 2];

        // -n
        yield ['-n', '{"a":-1,"b":0}', 1];

        // <ndashdigit-dimension>
        yield ['3n-4', '{"a":3,"b":-4}', 1];
        yield ['-3n-4', '{"a":-3,"b":-4}', 1];

        // '+'? <ndashdigit-ident>
        yield ['n-4', '{"a":1,"b":-4}', 1];
        yield ['+n-4', '{"a":1,"b":-4}', 2];

        // <dashndashdigit-ident>
        yield ['-n-4', '{"a":-1,"b":-4}', 1];

        // <n-dimension> <signed-integer>
        yield ['3n+4', '{"a":3,"b":4}', 2];
        yield ['3n +4', '{"a":3,"b":4}', 3];

        // '+'? n <signed-integer>
        yield ['n +4', '{"a":1,"b":4}', 3];
        yield ['+n -4', '{"a":1,"b":-4}', 4];

        // -n <signed-integer>
        yield ['-n +4', '{"a":-1,"b":4}', 3];
        yield ['-n -4', '{"a":-1,"b":-4}', 3];

        // <ndash-dimension> <signless-integer>
        yield ['3n- 4', '{"a":3,"b":-4}', 3];

        // '+'? n- <signless-integer>
        yield ['n- 4', '{"a":1,"b":-4}', 3];
        yield ['+n- 4', '{"a":1,"b":-4}', 4];

        // -n- <signless-integer>
        yield ['-n- 4', '{"a":-1,"b":-4}', 3];

        // <n-dimension> ['+' | '-'] <signless-integer>
        yield ['3n+ 4', '{"a":3,"b":4}', 4];
        yield ['3n + 4', '{"a":3,"b":4}', 5];
        yield ['3n - 4', '{"a":3,"b":-4}', 5];

        // '+'? n ['+' | '-'] <signless-integer>
        yield ['n + 4', '{"a":1,"b":4}', 5];
        yield ['+n - 4', '{"a":1,"b":-4}', 6];

        // -n ['+' | '-'] <signless-integer>
        yield ['-n + 4', '{"a":-1,"b":4}', 5];
        yield ['-n - 4', '{"a":-1,"b":-4}', 5];

        // ignore the rest
        yield ['-n+a', '{"a":-1,"b":0}', 1];
        yield ['3n+a', '{"a":3,"b":0}', 1];
        yield ['n + 1.5', '{"a":1,"b":0}', 1];

        // invalid cases
        yield ['a+b', 'null', 0];
        yield ['3rem', 'null', 0];
        yield ['-n- a', 'null', 0];
    }

    #[DataProvider('provideTryParse')]
    public function testTryParse(string $input, string $expected, int $indexAfter): void
    {
        $errors = new ParseExceptionCollection();
        $textStream = new TextStream($input, $errors);
        $tokenizer = new Tokenizer();
        $tokenStream = $tokenizer->convertToTokenStream($textStream, false);

        $parser = new AnbParser();
        $result = $parser->tryParse($tokenStream);

        static::assertSame($expected, Json::encode($result));
        static::assertSame($indexAfter, $tokenStream->position);
    }
}
