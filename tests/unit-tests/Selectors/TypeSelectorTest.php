<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\TypeSelector;
use PHPUnit\Framework\TestCase;

class TypeSelectorTest extends TestCase
{
    public function testToString(): void
    {
        $wqName = new WqName(false, null, 'div');
        $selector = new TypeSelector($wqName);
        self::assertEquals('div', $selector->__toString());
    }
}
