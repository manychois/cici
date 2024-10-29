<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Parsing\AnbNotation;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\PseudoClasses\ChildIndexedPseudoClass;
use PHPUnit\Framework\TestCase;

class ChildIndexedPseudoClassTest extends TestCase
{
    public function testMatchesWithNoParent(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ChildIndexedPseudoClass('first-child', null);

        self::assertFalse($selector->matches($context, $root));
    }

    public function testMatchesFirstChild(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $ul = $doc->createElement('ul');
        $li1 = $doc->createElement('li');
        $li2 = $doc->createElement('li');
        $li3 = $doc->createElement('li');

        $ul->appendChild($li1);
        $ul->appendChild($li2);
        $ul->appendChild($li3);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ChildIndexedPseudoClass('first-child', null);

        self::assertTrue($selector->matches($context, $li1));
        self::assertFalse($selector->matches($context, $li2));
        self::assertFalse($selector->matches($context, $li3));
    }

    public function testMatchesLastChild(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $ul = $doc->createElement('ul');
        $li1 = $doc->createElement('li');
        $li2 = $doc->createElement('li');
        $li3 = $doc->createElement('li');

        $ul->appendChild($li1);
        $ul->appendChild($li2);
        $ul->appendChild($li3);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ChildIndexedPseudoClass('last-child', null);

        self::assertFalse($selector->matches($context, $li1));
        self::assertFalse($selector->matches($context, $li2));
        self::assertTrue($selector->matches($context, $li3));
    }

    public function testMatchesOnlyChild(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $ul = $doc->createElement('ul');
        $li1 = $doc->createElement('li');

        $ul->appendChild($li1);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ChildIndexedPseudoClass('only-child', null);

        self::assertTrue($selector->matches($context, $li1));

        $li2 = $doc->createElement('li');
        $ul->appendChild($li2);

        self::assertFalse($selector->matches($context, $li1));
        self::assertFalse($selector->matches($context, $li2));
    }

    public function testMatchesNthChild(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $ul = $doc->createElement('ul');
        $li1 = $doc->createElement('li');
        $li2 = $doc->createElement('li');
        $li2->setAttribute('class', 'foo');
        $li3 = $doc->createElement('li');
        $li3->setAttribute('class', 'foo');

        $ul->appendChild($li1);
        $ul->appendChild($li2);
        $ul->appendChild($li3);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ChildIndexedPseudoClass('nth-child', new AnbNotation(2, 1));

        self::assertTrue($selector->matches($context, $li1));
        self::assertFalse($selector->matches($context, $li2));
        self::assertTrue($selector->matches($context, $li3));

        $selector = new ChildIndexedPseudoClass('nth-child', new AnbNotation(2, 1), new ClassSelector('foo'));

        self::assertFalse($selector->matches($context, $li1));
        self::assertTrue($selector->matches($context, $li2));
        self::assertFalse($selector->matches($context, $li3));
    }

    public function testMatchesNthLastChild(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $ul = $doc->createElement('ul');
        $li1 = $doc->createElement('li');
        $li2 = $doc->createElement('li');
        $li2->setAttribute('class', 'foo');
        $li3 = $doc->createElement('li');
        $li3->setAttribute('class', 'foo');

        $ul->appendChild($li1);
        $ul->appendChild($li2);
        $ul->appendChild($li3);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ChildIndexedPseudoClass('nth-last-child', new AnbNotation(2, 1));

        self::assertTrue($selector->matches($context, $li1));
        self::assertFalse($selector->matches($context, $li2));
        self::assertTrue($selector->matches($context, $li3));

        $selector = new ChildIndexedPseudoClass('nth-last-child', new AnbNotation(2, 1), new ClassSelector('foo'));

        self::assertFalse($selector->matches($context, $li1));
        self::assertFalse($selector->matches($context, $li2));
        self::assertTrue($selector->matches($context, $li3));
    }

    public function testMatchesWrongName(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected pseudo-class :foo.');

        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ChildIndexedPseudoClass('foo', null);
        $selector->matches($context, $root);
    }

    public function testToString(): void
    {
        $selector = new ChildIndexedPseudoClass('first-child', null);
        $this->assertSame(':first-child', $selector->__toString());

        $selector = new ChildIndexedPseudoClass('nth-child', new AnbNotation(2, 1));
        $this->assertSame(':nth-child(2n+1)', $selector->__toString());

        $selector = new ChildIndexedPseudoClass('nth-child', new AnbNotation(2, 1), new ClassSelector('foo'));
        $this->assertSame(':nth-child(2n+1 of .foo)', $selector->__toString());
    }
}
