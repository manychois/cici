<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\Combinator;
use Manychois\Cici\Selectors\ComplexSelector;
use PHPUnit\Framework\TestCase;

class ComplexSelectorTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $root->setAttribute('class', 'one');
        $doc->appendChild($root);
        $head = $doc->createElement('head');
        $root->appendChild($head);
        $title = $doc->createElement('title');
        $title->setAttribute('class', 'three');
        $head->appendChild($title);
        $body = $doc->createElement('body');
        $body->setAttribute('class', 'two');
        $root->appendChild($body);
        $div = $doc->createElement('div');
        $div->setAttribute('class', 'three');
        $body->appendChild($div);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ComplexSelector([
            new ClassSelector('one'),
            new ClassSelector('two'),
            new ClassSelector('three'),
            ], [
            Combinator::Descendant,
            Combinator::Descendant,
        ]);

        $this->assertFalse($selector->matches($context, $root));
        $this->assertFalse($selector->matches($context, $head));
        $this->assertFalse($selector->matches($context, $title));
        $this->assertFalse($selector->matches($context, $body));
        $this->assertTrue($selector->matches($context, $div));
    }

    public function testToString(): void
    {
        $selector = new ComplexSelector([
            new ClassSelector('one'),
            new ClassSelector('two'),
            new ClassSelector('three'),
            new ClassSelector('four'),
            new ClassSelector('five'),
            new ClassSelector('six'),
            ], [
            Combinator::Child,
            Combinator::Column,
            Combinator::Descendant,
            Combinator::NextSibling,
            Combinator::SubsequentSibling,
        ]);

        $this->assertEquals('.one>.two||.three .four+.five~.six', $selector->__toString());
    }
}
