<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\ForgivingSelectorList;
use Manychois\Cici\Selectors\PseudoClasses\IsWherePseudoClass;
use PHPUnit\Framework\TestCase;

class IsWherePseudoClassTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $inner = new ForgivingSelectorList([new ClassSelector('a')]);
        $selector = new IsWherePseudoClass('is', $inner);

        self::assertFalse($selector->matches($context, $root));
        $root->setAttribute('class', 'a');
        self::assertTrue($selector->matches($context, $root));
    }

    public function testToString(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $inner = new ForgivingSelectorList([new ClassSelector('a'), new ClassSelector('b')]);
        $selector = new IsWherePseudoClass('where', $inner);

        self::assertEquals(':where(.a,.b)', $selector->__toString());
    }
}
