<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\AbstractSelector;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\ForgivingSelectorList;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ForgivingSelectorListTest extends TestCase
{
    public static function provideMatches(): \Generator
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $root->setAttribute('class', 'one');
        $doc->appendChild($root);
        $head = $doc->createElement('head');
        $root->appendChild($head);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $fsList = new ForgivingSelectorList([]);

        yield [$fsList, $context, $root, false];

        $classSelector = new ClassSelector('one');
        $fsList = new ForgivingSelectorList([$classSelector]);

        yield [$fsList, $context, $root, true];

        yield [$fsList, $context, $head, false];

        $invalidSelector = new class extends AbstractSelector {
            public function matches(AbstractMatchContext $context, object $target): bool
            {
                throw new \Exception('Error');
            }

            public function jsonSerialize(): mixed
            {
                return [];
            }

            public function __toString(): string
            {
                return '';
            }
        };

        $fsList = new ForgivingSelectorList([$invalidSelector, $classSelector]);

        yield [$fsList, $context, $root, true];
    }

    #[DataProvider('provideMatches')]
    public function testMatches(
        ForgivingSelectorList $selector,
        DomNodeMatchContext $context,
        \DOMNode $target,
        bool $expected
    ): void {
        self::assertEquals($expected, $selector->matches($context, $target));
    }

    public function testToString(): void
    {
        $one = new ClassSelector('one');
        $two = new ClassSelector('two');
        $fsList = new ForgivingSelectorList([$one, $two]);

        self::assertEquals('.one,.two', $fsList->__toString());
    }
}
