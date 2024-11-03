<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\LogicalSelector;
use PHPUnit\Framework\TestCase;

class LogicalSelectorTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $root->setAttribute('class', 'a b');
        $doc->appendChild($root);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $a = new ClassSelector('a');
        $b = new ClassSelector('b');
        $selector = new LogicalSelector(true, [$a, $b]);
        self::assertTrue($selector->matches($context, $root));

        $root->setAttribute('class', 'a c');
        self::assertFalse($selector->matches($context, $root));

        $selector = new LogicalSelector(false, [$a, $b]);
        self::assertTrue($selector->matches($context, $root));

        $root->setAttribute('class', 'b c');
        self::assertTrue($selector->matches($context, $root));

        $root->setAttribute('class', 'c d');
        self::assertFalse($selector->matches($context, $root));
    }

    public function testToString(): void
    {
        $a = new ClassSelector('a');
        $b = new ClassSelector('b');
        $selector = new LogicalSelector(true, [$a, $b]);
        self::assertEquals('.a.b', $selector->__toString());

        $selector = new LogicalSelector(false, [$a, $b]);
        self::assertEquals('.a,.b', $selector->__toString());
    }
}
