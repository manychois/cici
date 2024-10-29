<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\PseudoClasses\AnyLinkPseudoClass;
use PHPUnit\Framework\TestCase;

class AnyLinkPseudoClassTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $a = $doc->createElement('a');
        $body->appendChild($a);
        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new AnyLinkPseudoClass();

        self::assertFalse($selector->matches($context, $root));
        self::assertFalse($selector->matches($context, $a));

        $a->setAttribute('href', 'http://example.com');
        self::assertTrue($selector->matches($context, $a));
    }
}
