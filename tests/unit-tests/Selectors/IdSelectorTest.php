<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\IdSelector;
use PHPUnit\Framework\TestCase;

class IdSelectorTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $root->setAttribute('id', 'one');
        $doc->appendChild($root);
        $head = $doc->createElement('head');
        $root->appendChild($head);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $idSelector = new IdSelector('one');
        self::assertTrue($idSelector->matches($context, $root));
        self::assertFalse($idSelector->matches($context, $head));
    }

    public function testToString(): void
    {
        $idSelector = new IdSelector('one');
        self::assertEquals('#one', $idSelector->__toString());

        $idSelector = new IdSelector('#a.b');
        self::assertEquals('#\23 a\2E b', $idSelector->__toString());
    }
}
