<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Matching;

use Manychois\Cici\Matching\DomNodeMatchContext;
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
        $this->assertCount(2, $children);
        $this->assertSame($head, $children[0]);
        $this->assertSame($body, $children[1]);
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
        $this->assertEquals('en', $context->getAttributeValue($html, 'lang'));
        $wqName = new WqName(false, null, 'lang');
        $this->assertEquals('en', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, null, 'lang');
        $this->assertEquals('en', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, 'a', 'lang');
        $this->assertEquals('zh-Hant', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, 'b', 'lang');
        $this->assertEquals('zh-Hans', $context->getAttributeValue($html, $wqName));
        $wqName = new WqName(true, '*', 'lang');
        $this->assertEquals('zh-Hant', $context->getAttributeValue($html, $wqName));
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
        $this->assertSame($expected, $context->matchElementType($element, $wqName));
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
}
