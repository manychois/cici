<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\AttributeSelector;
use Manychois\Cici\Selectors\AttrMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AttributeSelectorTest extends TestCase
{
    public static function provideMatches(): \Generator
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $scope = $root;
        $nsLookup = [
            'a' => 'https://a.test',
            'b' => 'https://b.test',
        ];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $langWqName = new WqName(false, null, 'lang');
        $selector = new AttributeSelector($langWqName, AttrMatcher::Exists, '', null);
        $target = $doc->createElement('body');

        yield [$selector, $context, $target, false];

        $selector = new AttributeSelector($langWqName, AttrMatcher::Exists, '', null);
        $target = $doc->createElement('body');
        $target->setAttribute('lang', 'en');

        yield [$selector, $context, $target, true];

        $selector = new AttributeSelector($langWqName, AttrMatcher::Exact, 'en', null);
        $target = $doc->createElement('body');
        $target->setAttribute('lang', 'EN');

        yield [$selector, $context, $target, false];

        $selector = new AttributeSelector($langWqName, AttrMatcher::Exact, 'en', false);
        $target = $doc->createElement('body');
        $target->setAttribute('lang', 'EN');

        yield [$selector, $context, $target, true];

        $classWqName = new WqName(false, null, 'class');
        $selector = new AttributeSelector($classWqName, AttrMatcher::Includes, 'on', true);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'one two three');

        yield [$selector, $context, $target, false];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Includes, 'two', null);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'one two three');

        yield [$selector, $context, $target, true];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Includes, 'one two', null);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'one two three');

        yield [$selector, $context, $target, false];

        $selector = new AttributeSelector($langWqName, AttrMatcher::Hyphen, 'zh', null);
        $target = $doc->createElement('body');
        $target->setAttribute('lang', 'zh-Hant');

        yield [$selector, $context, $target, true];

        $selector = new AttributeSelector($langWqName, AttrMatcher::Hyphen, 'zh', null);
        $target = $doc->createElement('body');
        $target->setAttribute('lang', 'zh');

        yield [$selector, $context, $target, true];

        $selector = new AttributeSelector($langWqName, AttrMatcher::Hyphen, 'zh', null);
        $target = $doc->createElement('body');
        $target->setAttribute('lang', 'en');

        yield [$selector, $context, $target, false];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Prefix, 'one two', null);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'one two three');

        yield [$selector, $context, $target, true];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Prefix, 'one two', null);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'three one two');

        yield [$selector, $context, $target, false];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Suffix, 'one two', null);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'one two three');

        yield [$selector, $context, $target, false];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Suffix, 'one TWO', false);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'three one two');

        yield [$selector, $context, $target, true];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Substring, 'on', null);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'three one two');

        yield [$selector, $context, $target, true];

        $selector = new AttributeSelector($classWqName, AttrMatcher::Substring, 'four', null);
        $target = $doc->createElement('body');
        $target->setAttribute('class', 'three one two');

        yield [$selector, $context, $target, false];
    }

    public static function provideToString(): \Generator
    {
        $wqName = new WqName(false, null, 'lang');
        $selector = new AttributeSelector($wqName, AttrMatcher::Exists, '', null);

        yield [$selector, '[lang]'];

        $wqName = new WqName(false, null, 'lang');
        $selector = new AttributeSelector($wqName, AttrMatcher::Exact, 'en', null);

        yield [$selector, '[lang="en"]'];

        $wqName = new WqName(true, '*', 'lang');
        $selector = new AttributeSelector($wqName, AttrMatcher::Includes, 'en', false);

        yield [$selector, '[*|lang~="en" i]'];

        $wqName = new WqName(true, 'test', 'data');
        $selector = new AttributeSelector($wqName, AttrMatcher::Hyphen, 'abc', true);

        yield [$selector, '[test|data|="abc" s]'];

        $wqName = new WqName(true, null, 'data');
        $selector = new AttributeSelector($wqName, AttrMatcher::Prefix, 'abc', null);

        yield [$selector, '[|data^="abc"]'];

        $wqName = new WqName(false, null, 'class');
        $selector = new AttributeSelector($wqName, AttrMatcher::Suffix, 'ABC', null);

        yield [$selector, '[class$="ABC"]'];

        $wqName = new WqName(false, null, 'class');
        $selector = new AttributeSelector($wqName, AttrMatcher::Substring, '123', null);

        yield [$selector, '[class*="123"]'];
    }

    #[DataProvider('provideMatches')]
    public function testMatches(
        AttributeSelector $selector,
        AbstractMatchContext $context,
        object $target,
        bool $expected
    ): void {
        $this->assertEquals($expected, $selector->matches($context, $target));
    }

    #[DataProvider('provideToString')]
    public function testToString(AttributeSelector $selector, string $expected): void
    {
        $this->assertEquals($expected, $selector->__toString());
    }
}
