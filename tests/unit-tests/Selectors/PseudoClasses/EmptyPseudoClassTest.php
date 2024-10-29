<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\PseudoClasses\EmptyPseudoClass;
use PHPUnit\Framework\TestCase;

class EmptyPseudoClassTest extends TestCase
{
    public function testMatches(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new EmptyPseudoClass();

        self::assertTrue($selector->matches($context, $root));

        $comment = $doc->createComment('comment');
        $root->appendChild($comment);
        self::assertTrue($selector->matches($context, $root));

        $cdc = $doc->createCDATASection('data');
        $root->appendChild($cdc);
        self::assertTrue($selector->matches($context, $root));

        $text = $doc->createTextNode(' ');
        $root->appendChild($text);
        self::assertFalse($selector->matches($context, $root));
    }
}
