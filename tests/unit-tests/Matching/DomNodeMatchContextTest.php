<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Matching;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Parsing\WqName;
use PHPUnit\Framework\TestCase;

class DomNodeMatchContextTest extends TestCase
{
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Namespace prefix not found: "b".');
        $wqName = new WqName(true, 'b', 'lang');
        $context->getAttributeValue($html, $wqName);
    }
}
