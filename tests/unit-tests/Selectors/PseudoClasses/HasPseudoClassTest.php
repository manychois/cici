<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\Combinator;
use Manychois\Cici\Selectors\PseudoClasses\HasPseudoClass;
use Manychois\Cici\Selectors\RelativeSelector;
use PHPUnit\Framework\TestCase;

class HasPseudoClassTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $div = $doc->createElement('div');
        $div->setAttribute('class', 'a');
        $body->appendChild($div);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $relativeSelector = new RelativeSelector(Combinator::Descendant, new ClassSelector('a'));
        $selector = new HasPseudoClass($relativeSelector);

        self::assertTrue($selector->matches($context, $root));
        self::assertTrue($selector->matches($context, $body));
        self::assertFalse($selector->matches($context, $div));
    }

    public function testToString(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $relativeSelector = new RelativeSelector(Combinator::Descendant, new ClassSelector('a'));
        $selector = new HasPseudoClass($relativeSelector);

        self::assertEquals(':has(.a)', $selector->__toString());
    }
}
