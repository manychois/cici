<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\ClassSelector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClassSelectorTest extends TestCase
{
    public static function provideMatches(): \Generator
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $root->setAttribute('class', 'one two three');
        $doc->appendChild($root);
        $head = $doc->createElement('head');
        $root->appendChild($head);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new ClassSelector('two');

        yield [$selector, $context, $root, true];

        $selector = new ClassSelector('one two');

        yield [$selector, $context, $root, false];

        $selector = new ClassSelector('one');

        yield [$selector, $context, $head, false];
    }

    public static function provideToString(): \Generator
    {
        $selector = new ClassSelector('one');

        yield [$selector, '.one'];

        $selector = new ClassSelector('one two');

        yield [$selector, '.one\\20 two'];

        $selector = new ClassSelector('.dot');

        yield [$selector, '.\\2E dot'];
    }

    #[DataProvider('provideMatches')]
    public function testMatches(
        ClassSelector $selector,
        AbstractMatchContext $context,
        object $target,
        bool $expected
    ): void {
        $this->assertEquals($expected, $selector->matches($context, $target));
    }

    #[DataProvider('provideToString')]
    public function testToString(ClassSelector $selector, string $expected): void
    {
        $this->assertEquals($expected, $selector->__toString());
    }
}
