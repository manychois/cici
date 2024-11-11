<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\PseudoClasses\NotPseudoClass;
use PHPUnit\Framework\TestCase;

class NotPseudoClassTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $inner = new ClassSelector('a');
        $selector = new NotPseudoClass($inner);

        self::assertTrue($selector->matches($context, $root));
        $root->setAttribute('class', 'a');
        self::assertFalse($selector->matches($context, $root));
    }

    public function testToString(): void
    {
        $inner = new ClassSelector('a');
        $selector = new NotPseudoClass($inner);

        self::assertEquals(':not(.a)', $selector->__toString());
    }
}
