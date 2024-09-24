<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Exceptions;

use Manychois\Cici\Exceptions\ParseException;
use Manychois\Cici\Exceptions\ParseExceptionCollection;
use PHPUnit\Framework\TestCase;

class ParseExceptionCollectionTest extends TestCase
{
    public function testGet_indexOutOfRangeThrowsException(): void
    {
        $collection = new ParseExceptionCollection();
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Index out of range.');
        $collection->get(0);
    }

    public function testGetIterator(): void
    {
        $collection = new ParseExceptionCollection();
        $a = new ParseException('a', 0);
        $b = new ParseException('b', 1);
        $collection->add($a);
        $collection->add($b);

        $result = [];
        foreach ($collection as $error) {
            $result[] = $error;
        }

        $this->assertEquals([$a, $b], $result);
    }
}
