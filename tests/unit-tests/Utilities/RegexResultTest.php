<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Utilities;

use Manychois\Cici\Utilities\RegexResult;
use PHPUnit\Framework\TestCase;

class RegexResultTest extends TestCase
{
    public function testMatches(): void
    {
        $result = RegexResult::matches('/\d+/', 'abc123def456', 7, true);
        self::assertTrue($result->success);
        self::assertSame('456', $result->value);
        self::assertSame(9, $result->offset);

        $result = RegexResult::matches('/\d+/', 'abcdef');
        self::assertFalse($result->success);
        self::assertSame('', $result->value);
        self::assertSame(-1, $result->offset);

        $result = RegexResult::matches('/\d+/', 'abc123def456');
        self::assertTrue($result->success);
        self::assertSame('123', $result->value);
        self::assertSame(-1, $result->offset);
    }

    public function testMatches_invalidPattern(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The regular expression pattern is invalid.');
        RegexResult::matches('\d+', 'abcdef', 0, true);
    }
}
