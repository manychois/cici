<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\PseudoElementSelector;
use PHPUnit\Framework\TestCase;

class PseudoElementSelectorTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Matching pseudo-elements is not supported.');
        $selector = new PseudoElementSelector('before', false);
        $selector->matches($context, $root);
    }

    public function testToString(): void
    {
        $selector = new PseudoElementSelector('before', false);
        $this->assertEquals('::before', $selector->__toString());
    }
}
