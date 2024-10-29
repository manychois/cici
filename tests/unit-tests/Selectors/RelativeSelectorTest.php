<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\Combinator;
use Manychois\Cici\Selectors\RelativeSelector;
use PHPUnit\Framework\TestCase;

class RelativeSelectorTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $head = $doc->createElement('head');
        $head->setAttribute('class', 'a');
        $root->appendChild($head);
        $body = $doc->createElement('body');
        $body->setAttribute('class', 'b');
        $root->appendChild($body);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new RelativeSelector(Combinator::Child, new ClassSelector('a'));
        static::assertTrue($selector->matches($context, $root));

        $selector = new RelativeSelector(Combinator::Child, new ClassSelector('b'));
        static::assertTrue($selector->matches($context, $root));

        $selector = new RelativeSelector(Combinator::Child, new ClassSelector('c'));
        static::assertFalse($selector->matches($context, $root));
    }

    public function testToString(): void
    {
        $selector = new RelativeSelector(Combinator::Child, new ClassSelector('a'));
        $this->assertEquals('>.a', $selector->__toString());

        $selector = new RelativeSelector(Combinator::Descendant, new ClassSelector('a'));
        $this->assertEquals('.a', $selector->__toString());
    }
}
