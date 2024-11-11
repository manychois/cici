<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Matching;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Matching\NodeType;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\Combinator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DomNodeMatchContextTest extends TestCase
{
    public static function provideLoopLeftCandidates(): \Generator
    {
        $html = <<<'HTML'
        <html>
            <!-- comment -->
            <head>
                <title>Test</title>
                <meta charset="UTF-8">
            </head>
            <body>
                <div>
                    <p>One</p>
                    <p>
                        <a href="#">Link</a>
                        <b>Two</b> Three <i>Four</i>
                    </p>
                </div>
                <div>
                    Five
                    <p>Six</p>
                    Seven
                </div>
            </body>
        </html>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $context = new DomNodeMatchContext($doc, $doc, []);

        $firstI = null;
        foreach ($context->loopDescendants($doc, false) as $ele) {
            if ($firstI !== null && $ele->localName !== 'i') {
                continue;
            }

            $firstI = $ele;
        }

        yield [$context, $firstI, Combinator::Descendant, 'p,div,body,html'];
        yield [$context, $firstI, Combinator::Child, 'p'];
        yield [$context, $firstI, Combinator::NextSibling, 'b'];
        yield [$context, $firstI, Combinator::SubsequentSibling, 'b,a'];
    }

    public static function provideLoopRightCandidates(): \Generator
    {
        $html = <<<'HTML'
        <html>
            <!-- comment -->
            <head>
                <title>Test</title>
                <meta charset="UTF-8">
            </head>
            <body>
                <div>
                    <p>One</p>
                    <p>
                        <a href="#">Link</a>
                        <b>Two</b> Three <i>Four</i>
                    </p>
                </div>
                <div>
                    Five
                    <p>Six</p>
                    Seven
                </div>
            </body>
        </html>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $context = new DomNodeMatchContext($doc, $doc, []);

        $body = null;
        $a = null;
        foreach ($context->loopDescendants($doc, false) as $ele) {
            if ($body === null && $ele->localName === 'body') {
                $body = $ele;
            }
            if ($a !== null || $ele->localName !== 'a') {
                continue;
            }

            $a = $ele;
        }

        yield [$context, $body, Combinator::Descendant, 'div,p,p,a,b,i,div,p'];
        yield [$context, $body, Combinator::Child, 'div,div'];
        yield [$context, $a, Combinator::NextSibling, 'b'];
        yield [$context, $a, Combinator::SubsequentSibling, 'b,i'];
    }

    public static function provideMatchElementType(): \Generator
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $svg = $doc->createElementNS('http://www.w3.org/2000/svg', 'abc:svg');
        $html->appendChild($svg);

        $context1 = new DomNodeMatchContext($doc, $html, ['a' => 'http://www.w3.org/2000/svg']);

        yield [$context1, $html, new WqName(true, null, 'html'), true];
        yield [$context1, $html, new WqName(true, '*', 'html'), true];
        yield [$context1, $html, new WqName(true, 'a', 'html'), false];
        yield [$context1, $html, new WqName(true, null, 'svg'), false];
        yield [$context1, $html, new WqName(true, 'a', 'svg'), false];
        yield [$context1, $html, new WqName(false, null, '*'), true];
        yield [$context1, $html, new WqName(true, '*', '*'), true];
        yield [$context1, $svg, new WqName(false, null, '*'), true];
        yield [$context1, $svg, new WqName(false, null, 'svg'), true];
        yield [$context1, $svg, new WqName(true, 'a', 'svg'), true];
        yield [$context1, $svg, new WqName(true, null, 'svg'), false];

        $context2 = new DomNodeMatchContext($doc, $html, ['' => 'http://www.w3.org/2000/svg']);

        yield [$context2, $html, new WqName(true, null, 'html'), true];
        yield [$context2, $html, new WqName(true, '*', 'html'), true];
        yield [$context2, $html, new WqName(false, null, 'html'), false];
        yield [$context2, $svg, new WqName(true, null, 'svg'), false];
        yield [$context2, $svg, new WqName(false, null, 'svg'), true];
    }

    public static function provideIsActuallyDisabled(): \Generator
    {
        $nsLookup = [];
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $context = new DomNodeMatchContext($html, $html, $nsLookup);

        yield [$context, $doc, false];

        $rawHtml = <<<'HTML'
        <html id="html">
            <form>
                <fieldset disabled>
                    <legend>
                        <!-- they are not considered as disabled -->
                        <span>Some text</span>
                        <button id="button">Some button</button>
                    </legend>
                    <input id="input1">
                </fieldset>
            </form>
        </html>
        HTML;
        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $html = $doc->getElementById('html');
        \assert($html !== null);
        $context = new DomNodeMatchContext($html, $html, $nsLookup);
        $input1 = $doc->getElementById('input1');

        yield [$context, $input1, true];

        $button = $doc->getElementById('button');

        yield [$context, $button, false];

        $rawHtml = <<<'HTML'
        <html id="html">
            <form>
                <fieldset>
                    <input id="input1">
                </fieldset>
            </form>
        </html>
        HTML;
        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $html = $doc->getElementById('html');
        \assert($html !== null);
        $context = new DomNodeMatchContext($html, $html, $nsLookup);
        $input1 = $doc->getElementById('input1');

        yield [$context, $input1, false];

        $rawHtml = <<<'HTML'
        <html id="html">
            <form>
                <fieldset disabled>
                    <fieldset id="fieldset1">
                    </fieldset>
                </fieldset>
            </form>
        </html>
        HTML;
        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $html = $doc->getElementById('html');
        \assert($html !== null);
        $context = new DomNodeMatchContext($html, $html, $nsLookup);
        $fieldset1 = $doc->getElementById('fieldset1');

        yield [$context, $fieldset1, true];

        $rawHtml = <<<'HTML'
        <html id="html">
            <optgroup id="optgroup" disabled>
                <option id="option1">Option 1</option>
                <option id="option2" disabled>Option 2</option>
            </optgroup>
        </html>
        HTML;
        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $html = $doc->getElementById('html');
        \assert($html !== null);
        $optgroup = $doc->getElementById('optgroup');
        $context = new DomNodeMatchContext($html, $html, $nsLookup);

        yield [$context, $optgroup, true];

        $option1 = $doc->getElementById('option1');

        yield [$context, $option1, true];

        $option2 = $doc->getElementById('option2');

        yield [$context, $option2, true];
    }

    public function testLoopChildren(): void
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $html->appendChild($doc->createComment('comment'));
        $head = $doc->createElement('head');
        $html->appendChild($doc->createTextNode('   '));
        $html->appendChild($head);
        $body = $doc->createElement('body');
        $html->appendChild($body);

        $context = new DomNodeMatchContext($html, $html, []);
        $children = \iterator_to_array($context->loopChildren($html));
        self::assertCount(2, $children);
        self::assertSame($head, $children[0]);
        self::assertSame($body, $children[1]);
    }

    public function testLoopDescendants(): void
    {
        $html = <<<'HTML'
        <html>
            <!-- comment -->
            <head>
                <title>Test</title>
                <meta charset="UTF-8">
            </head>
            <body>
                <div>
                    <p>One</p>
                    <p><b>Two</b> Three <i>Four</i></p>
                </div>
                <div>
                    Five
                    <p>Six</p>
                    Seven
                </div>
            </body>
        </html>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($html);

        $context = new DomNodeMatchContext($doc, $doc, []);
        $firstDiv = null;
        $tagNames = [];
        foreach ($context->loopDescendants($doc, false) as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            if ($firstDiv === null && $node->localName === 'div') {
                $firstDiv = $node;
            }
            $tagNames[] = $node->localName;
        }

        static::assertSame('html,head,title,meta,body,div,p,p,b,i,div,p', \implode(',', $tagNames));
        static::assertNotNull($firstDiv);

        $tagNames = [];
        foreach ($context->loopDescendants($firstDiv, true) as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $tagNames[] = $node->localName;
        }
        static::assertSame('div,p,p,b,i', \implode(',', $tagNames));
    }

    #[DataProvider('provideLoopLeftCandidates')]
    public function testLoopLeftCandidates(
        DomNodeMatchContext $context,
        \DOMElement $element,
        Combinator $combinator,
        string $expected
    ): void {
        $tagNames = [];
        foreach ($context->loopLeftCandidates($element, $combinator) as $ele) {
            $tagNames[] = $ele->nodeName;
        }
        static::assertSame($expected, \implode(',', $tagNames));
    }

    public function testLoopLeftCandidates_columnUnsupported(): void
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $context = new DomNodeMatchContext($html, $html, []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported combinator "||".');
        \iterator_to_array($context->loopLeftCandidates($html, Combinator::Column));
    }

    #[DataProvider('provideLoopRightCandidates')]
    public function testLoopRightCandidates(
        DomNodeMatchContext $context,
        \DOMElement $element,
        Combinator $combinator,
        string $expected
    ): void {
        $tagNames = [];
        foreach ($context->loopRightCandidates($element, $combinator) as $ele) {
            $tagNames[] = $ele->nodeName;
        }
        static::assertSame($expected, \implode(',', $tagNames));
    }

    public function testLoopRightCandidates_columnUnsupported(): void
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $context = new DomNodeMatchContext($html, $html, []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported combinator "||".');
        \iterator_to_array($context->loopRightCandidates($html, Combinator::Column));
    }

    public function testGetAttributeValue(): void
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);

        $aLang = $doc->createAttributeNS('https://a.test', 'test-a:lang');
        $aLang->value = 'zh-Hant';
        $html->setAttributeNodeNS($aLang);
        $bLang = $doc->createAttributeNS('https://b.test', 'test-b:lang');
        $bLang->value = 'zh-Hans';
        $html->setAttributeNodeNS($bLang);
        $html->setAttribute('lang', 'en');
        $nsLookup = [
            'a' => 'https://a.test',
            'b' => 'https://b.test',
        ];

        $context = new DomNodeMatchContext($html, $html, $nsLookup);
        self::assertEquals('en', $context->getAttributeValue($html, 'lang'));
        $wqName = new WqName(false, null, 'lang');
        self::assertEquals('en', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, null, 'lang');
        self::assertEquals('en', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, 'a', 'lang');
        self::assertEquals('zh-Hant', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, 'b', 'lang');
        self::assertEquals('zh-Hans', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, '*', 'lang');
        self::assertEquals('zh-Hant', $context->getAttributeValue($html, $wqName));
    }

    public function testGetAttributeValue_errorOnInvalidPrefix(): void
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $nsLookup = [
            '' => 'https://a.test',
            'a' => 'https://a.test',
        ];

        $context = new DomNodeMatchContext($html, $html, $nsLookup);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Namespace prefix not found: "b".');
        $wqName = new WqName(true, 'b', 'lang');
        $context->getAttributeValue($html, $wqName);
    }

    #[DataProvider('provideMatchElementType')]
    public function testMatchElementType(
        DomNodeMatchContext $context,
        \DOMElement $element,
        WqName $wqName,
        bool $expected
    ): void {
        self::assertSame($expected, $context->matchElementType($element, $wqName));
    }

    public function testMatchElementType_errorOnInvalidPrefix(): void
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $doc->appendChild($html);
        $nsLookup = [
            '' => 'https://a.test',
            'a' => 'https://a.test',
        ];

        $context = new DomNodeMatchContext($html, $html, $nsLookup);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Namespace prefix not found: "b".');
        $wqName = new WqName(true, 'b', 'lang');
        $context->matchElementType($html, $wqName);
    }

    public function testMatchDefaultNamespace(): void
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $custom = $doc->createElementNS('https://a.test', 'test:custom');
        $nsLookup = ['' => 'https://a.test'];
        $context1 = new DomNodeMatchContext($html, $html, $nsLookup);

        static::assertFalse($context1->matchDefaultNamespace($html));
        static::assertTrue($context1->matchDefaultNamespace($custom));

        $context2 = new DomNodeMatchContext($html, $html, []);
        static::assertTrue($context2->matchDefaultNamespace($html));
        static::assertTrue($context2->matchDefaultNamespace($custom));
    }

    public function testGetAttributeValeOnNonElement(): void
    {
        $context = $this->createSimpleMatchContext();
        $value = $context->getAttributeValue(new \DOMDocument(), 'class');
        static::assertNull($value);
    }

    public function testGetNodeType(): void
    {
        $context = $this->createSimpleMatchContext();
        $doc = new \DOMDocument();
        static::assertSame(NodeType::Document, $context->getNodeType($doc));

        $html = $doc->createElement('html');
        static::assertSame(NodeType::Element, $context->getNodeType($html));

        $text = $doc->createTextNode('text');
        static::assertSame(NodeType::Text, $context->getNodeType($text));

        $comment = $doc->createComment('comment');
        static::assertSame(NodeType::Comment, $context->getNodeType($comment));

        $doctype = $doc->implementation->createDocumentType('html');
        static::assertSame(NodeType::DocumentType, $context->getNodeType($doctype));

        $fragment = $doc->createDocumentFragment();
        static::assertSame(NodeType::DocumentFragment, $context->getNodeType($fragment));

        $cdata = $doc->createCDATASection('cdata');
        static::assertSame(NodeType::Unsupported, $context->getNodeType($cdata));
    }

    public function testGetRadioButtonGroup(): void
    {
        $html = <<<'HTML'
        <form>
            <input type="radio" id="r1">
            <input type="radio" id="r2" name="group1">
            <input type="radio" id="r3" name="group2">
            <fieldset>
                <input type="radio" id="r4" name="group1">
                <input type="radio" id="r5" name="group2">
            </fieldset>
        </form>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $context = new DomNodeMatchContext($doc, $doc, []);

        $radios = [];
        for ($i = 1; $i <= 5; $i++) {
            $radio = $doc->getElementById('r' . $i);
            \assert($radio !== null);
            $radios[] = $radio;
        }

        $printGroup = static fn (array $group): string => \implode(
            ',',
            \array_map(static fn ($ele) => $ele instanceof \DOMElement ? $ele->getAttribute('id') : '', $group)
        );

        $group = $context->getRadioButtonGroup($radios[0]);
        static::assertSame('r1', $printGroup($group));
        $group = $context->getRadioButtonGroup($radios[1]);
        static::assertSame('r2,r4', $printGroup($group));
        $group = $context->getRadioButtonGroup($radios[2]);
        static::assertSame('r3,r5', $printGroup($group));
        $group = $context->getRadioButtonGroup($radios[3]);
        static::assertSame('r2,r4', $printGroup($group));
        $group = $context->getRadioButtonGroup($radios[4]);
        static::assertSame('r3,r5', $printGroup($group));

        $orphan = $doc->createElement('input');
        $orphan->setAttribute('id', 'r6');
        $orphan->setAttribute('type', 'radio');
        $orphan->setAttribute('name', 'group3');
        $group = $context->getRadioButtonGroup($orphan);
        static::assertSame('r6', $printGroup($group));
    }

    #[DataProvider('provideIsActuallyDisabled')]
    public function testIsActuallyDisabled(DomNodeMatchContext $context, \DOMNode $target, bool $expected): void
    {
        static::assertSame($expected, $context->isActuallyDisabled($target));
    }

    public function testIsReadWritableOnTextarea(): void
    {
        $rawHtml = <<<'HTML'
        <form id="form">
            <textarea id="ta1"></textarea>
            <textarea id="ta2" readonly></textarea>
            <textarea id="ta3" disabled></textarea>
        </form>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $form = $doc->getElementById('form');
        \assert($form !== null);
        $context = new DomNodeMatchContext($form, $form, []);

        $ta1 = $doc->getElementById('ta1');
        \assert($ta1 !== null);
        $ta2 = $doc->getElementById('ta2');
        \assert($ta2 !== null);
        $ta3 = $doc->getElementById('ta3');
        \assert($ta3 !== null);

        static::assertTrue($context->isReadWritable($ta1));
        static::assertFalse($context->isReadWritable($ta2));
        static::assertFalse($context->isReadWritable($ta3));
    }

    public function testMatchElementTypeOnNonElement(): void
    {
        $context = $this->createSimpleMatchContext();
        $doc = new \DOMDocument();
        $actual = $context->matchElementType($doc, new WqName(true, null, 'html'));
        static::assertFalse($actual);
    }

    private function createSimpleMatchContext(): DomNodeMatchContext
    {
        $doc = new \DOMDocument();
        $html = $doc->createElement('html');
        $nsLookup = [];

        return new DomNodeMatchContext($html, $html, $nsLookup);
    }
}
