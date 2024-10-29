<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Parsing;

use Manychois\Cici\Parsing\AnbNotation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AnbNotationTest extends TestCase
{
    public static function provideMatches(): \Generator
    {
        yield [0, 0, 0, false];
        yield [0, 2, 1, false];
        yield [0, 2, 2, true];
        yield [0, 2, 3, false];
        yield [1, -2, -1, false];
        yield [1, 2, 1, false];
        yield [1, 2, 2, true];
        yield [2, 1, 7, true];
        yield [-1, 4, 3, true];
        yield [-1, 4, 4, true];
        yield [-1, 4, 5, false];
    }

    public static function provideToString(): \Generator
    {
        yield [new AnbNotation(0, 1), '1'];
        yield [new AnbNotation(0, -2), '-2'];
        yield [new AnbNotation(1, 0), 'n'];
        yield [new AnbNotation(-1, 0), '-n'];
        yield [new AnbNotation(1, 1), 'n+1'];
        yield [new AnbNotation(-1, -2), '-n-2'];
        yield [new AnbNotation(3, -4), '3n-4'];
        yield [new AnbNotation(-3, 0), '-3n'];
        yield [new AnbNotation(2, 0, 'even'), 'even'];
        yield [new AnbNotation(2, 1, 'odd'), 'odd'];
    }

    #[DataProvider('provideMatches')]
    public function testMatches(int $a, int $b, int $index, bool $expected): void
    {
        $anb = new AnbNotation($a, $b);
        $this->assertSame($expected, $anb->matches($index));
    }

    #[DataProvider('provideToString')]
    public function testToString(AnbNotation $anb, string $expected): void
    {
        $this->assertSame($expected, $anb->__toString());
    }
}
