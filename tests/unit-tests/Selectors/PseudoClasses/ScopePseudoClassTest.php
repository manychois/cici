<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\PseudoClasses\ScopePseudoClass;
use PHPUnit\Framework\TestCase;

class ScopePseudoClassTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);

        $scope = $body;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ScopePseudoClass();

        self::assertFalse($selector->matches($context, $root));
        self::assertTrue($selector->matches($context, $body));
    }
}
