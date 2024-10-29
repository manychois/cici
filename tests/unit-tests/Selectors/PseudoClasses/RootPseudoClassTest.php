<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\PseudoClasses\RootPseudoClass;
use PHPUnit\Framework\TestCase;

class RootPseudoClassTest extends TestCase
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

        $selector = new RootPseudoClass();

        self::assertTrue($selector->matches($context, $root));
        self::assertFalse($selector->matches($context, $body));
    }
}
