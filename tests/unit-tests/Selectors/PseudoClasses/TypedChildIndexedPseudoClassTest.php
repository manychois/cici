<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Parsing\AnbNotation;
use Manychois\Cici\Selectors\PseudoClasses\TypedChildIndexedPseudoClass;
use PHPUnit\Framework\TestCase;

class TypedChildIndexedPseudoClassTest extends TestCase
{
    public function testMatchesWithNoParent(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new TypedChildIndexedPseudoClass('first-of-type', null);

        self::assertFalse($selector->matches($context, $root));
    }

    public function testMatchesFirstOfType(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $div = $doc->createElement('div');
        $a1 = $doc->createElement('a');
        $div->appendChild($a1);
        $b1 = $doc->createElement('b');
        $div->appendChild($b1);
        $a2 = $doc->createElement('a');
        $div->appendChild($a2);
        $b2 = $doc->createElement('b');
        $div->appendChild($b2);
        $a3 = $doc->createElement('a');
        $div->appendChild($a3);
        $b3 = $doc->createElement('b');
        $div->appendChild($b3);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new TypedChildIndexedPseudoClass('first-of-type', null);

        self::assertTrue($selector->matches($context, $a1));
        self::assertTrue($selector->matches($context, $b1));
        self::assertFalse($selector->matches($context, $a2));
        self::assertFalse($selector->matches($context, $b2));
        self::assertFalse($selector->matches($context, $a3));
        self::assertFalse($selector->matches($context, $b3));
    }

    public function testMatchesLastOfType(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $div = $doc->createElement('div');
        $a1 = $doc->createElement('a');
        $div->appendChild($a1);
        $b1 = $doc->createElement('b');
        $div->appendChild($b1);
        $a2 = $doc->createElement('a');
        $div->appendChild($a2);
        $b2 = $doc->createElement('b');
        $div->appendChild($b2);
        $a3 = $doc->createElement('a');
        $div->appendChild($a3);
        $b3 = $doc->createElement('b');
        $div->appendChild($b3);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new TypedChildIndexedPseudoClass('last-of-type', null);

        self::assertFalse($selector->matches($context, $a1));
        self::assertFalse($selector->matches($context, $b1));
        self::assertFalse($selector->matches($context, $a2));
        self::assertFalse($selector->matches($context, $b2));
        self::assertTrue($selector->matches($context, $a3));
        self::assertTrue($selector->matches($context, $b3));
    }

    public function testMatchesOnlyOfType(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $div = $doc->createElement('div');
        $a1 = $doc->createElement('a');
        $div->appendChild($a1);
        $b1 = $doc->createElement('b');
        $div->appendChild($b1);
        $a2 = $doc->createElement('a');
        $div->appendChild($a2);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new TypedChildIndexedPseudoClass('only-of-type', null);

        self::assertFalse($selector->matches($context, $a1));
        self::assertTrue($selector->matches($context, $b1));
        self::assertFalse($selector->matches($context, $a2));
    }

    public function testMatchesNthOfType(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $div = $doc->createElement('div');
        $a1 = $doc->createElement('a');
        $div->appendChild($a1);
        $b1 = $doc->createElement('b');
        $div->appendChild($b1);
        $a2 = $doc->createElement('a');
        $div->appendChild($a2);
        $b2 = $doc->createElement('b');
        $div->appendChild($b2);
        $a3 = $doc->createElement('a');
        $div->appendChild($a3);
        $b3 = $doc->createElement('b');
        $div->appendChild($b3);
        $a4 = $doc->createElement('a');
        $div->appendChild($a4);
        $b4 = $doc->createElement('b');
        $div->appendChild($b4);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new TypedChildIndexedPseudoClass('nth-of-type', new AnbNotation(2, 1));

        self::assertTrue($selector->matches($context, $a1));
        self::assertTrue($selector->matches($context, $b1));
        self::assertFalse($selector->matches($context, $a2));
        self::assertFalse($selector->matches($context, $b2));
        self::assertTrue($selector->matches($context, $a3));
        self::assertTrue($selector->matches($context, $b3));
        self::assertFalse($selector->matches($context, $a4));
        self::assertFalse($selector->matches($context, $b4));
    }

    public function testMatchesNthLastOfType(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $div = $doc->createElement('div');
        $a1 = $doc->createElement('a');
        $div->appendChild($a1);
        $b1 = $doc->createElement('b');
        $div->appendChild($b1);
        $a2 = $doc->createElement('a');
        $div->appendChild($a2);
        $b2 = $doc->createElement('b');
        $div->appendChild($b2);
        $a3 = $doc->createElement('a');
        $div->appendChild($a3);
        $b3 = $doc->createElement('b');
        $div->appendChild($b3);
        $a4 = $doc->createElement('a');
        $div->appendChild($a4);
        $b4 = $doc->createElement('b');
        $div->appendChild($b4);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new TypedChildIndexedPseudoClass('nth-last-of-type', new AnbNotation(2, 1));

        self::assertFalse($selector->matches($context, $a1));
        self::assertFalse($selector->matches($context, $b1));
        self::assertTrue($selector->matches($context, $a2));
        self::assertTrue($selector->matches($context, $b2));
        self::assertFalse($selector->matches($context, $a3));
        self::assertFalse($selector->matches($context, $b3));
        self::assertTrue($selector->matches($context, $a4));
        self::assertTrue($selector->matches($context, $b4));
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

        $selector = new TypedChildIndexedPseudoClass('foo', null);
        $selector->matches($context, $root);
    }

    public function testToString(): void
    {
        $selector = new TypedChildIndexedPseudoClass('first-of-type', null);
        self::assertSame(':first-of-type', $selector->__toString());

        $selector = new TypedChildIndexedPseudoClass('nth-of-type', new AnbNotation(2, 1));
        self::assertSame(':nth-of-type(2n+1)', $selector->__toString());
    }
}
