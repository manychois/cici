<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\PseudoClasses\UnknownPseudoClassSelector;
use Manychois\Cici\Tokenization\Tokens\StringToken;
use PHPUnit\Framework\TestCase;

class UnknownPseudoClassSelectorTest extends TestCase
{
    public function testMatches(): void
    {
        $selector = new UnknownPseudoClassSelector('next-gen', false);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Matching pseudo-class :next-gen is not supported.');

        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);
        $selector->matches($context, $root);
    }

    public function testToString(): void
    {
        $selector = new UnknownPseudoClassSelector('next-gen', true, new StringToken('foo', 0, 5));
        self::assertEquals(':next-gen("foo")', $selector->__toString());

        $selector = new UnknownPseudoClassSelector('next-gen', false);
        self::assertEquals(':next-gen', $selector->__toString());
    }
}
