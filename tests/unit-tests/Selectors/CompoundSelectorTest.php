<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\CompoundSelector;
use Manychois\Cici\Selectors\TypeSelector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CompoundSelectorTest extends TestCase
{
    public static function provideMatches(): \Generator
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $root->setAttribute('class', 'one');
        $doc->appendChild($root);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $classSelector = new ClassSelector('one');
        $compoundSelector = new CompoundSelector(null, [$classSelector], false);

        yield [$compoundSelector, $context, $root, true];

        $wqName = new WqName(false, null, 'html');
        $type = new TypeSelector($wqName);
        $compoundSelector = new CompoundSelector($type, [$classSelector], false);

        yield [$compoundSelector, $context, $root, true];

        $nsLookup = ['' => 'http://default-namespace.test'];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);
        $compoundSelector = new CompoundSelector(null, [$classSelector], false);

        yield [$compoundSelector, $context, $root, false];
    }

    #[DataProvider('provideMatches')]
    public function testMatches(
        CompoundSelector $selector,
        DomNodeMatchContext $context,
        \DOMNode $target,
        bool $expected
    ): void {
        self::assertEquals($expected, $selector->matches($context, $target));
    }
}
