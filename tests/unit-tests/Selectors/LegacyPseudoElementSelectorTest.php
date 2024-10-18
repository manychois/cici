<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Selectors\LegacyPseudoElementSelector;
use PHPUnit\Framework\TestCase;

class LegacyPseudoElementSelectorTest extends TestCase
{
    public function testToString(): void
    {
        $selector = new LegacyPseudoElementSelector('before');
        $this->assertEquals(':before', $selector->__toString());
    }
}
