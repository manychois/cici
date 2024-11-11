<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests;

use Manychois\Cici\DomQuery;
use Manychois\Cici\Exceptions\ParseException;
use PHPUnit\Framework\TestCase;

class DomQueryTest extends TestCase
{
    public function testQuery1(): void
    {
        $rawHtml = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
        </head>
        <body>
            <div>
                <p>Hello, world!</p>
            </div>
        </body>
        </html>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $query = new DomQuery();
        $ele = $query->query($doc, 'p');
        \assert($ele !== null);
        self::assertSame('Hello, world!', $ele->textContent);

        $head = $query->query($doc, 'head');
        self::assertNotNull($head);

        $body = $query->query($doc, 'body');
        self::assertNotNull($body);

        $title = $query->query($head, 'title');
        \assert($title !== null);
        self::assertSame('Test', $title->textContent);

        $title = $query->query($body, 'title');
        self::assertNull($title);
    }

    public function testQuery2(): void
    {
        $doc = new \DOMDocument();
        $fragment = $doc->createDocumentFragment();
        $fragment->append($doc->createElement('p', 'Hello, world!'));
        $query = new DomQuery();
        $ele = $query->query($fragment, ':scope > p');
        \assert($ele !== null);
        self::assertSame('Hello, world!', $ele->textContent);
    }

    public function testQueryInvalidNodeType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scope node type.');

        $query = new DomQuery();
        $query->query(new \DOMText('text'), 'p');
    }

    public function testQueryInvalidSelector(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unterminated comment.');

        $doc = new \DOMDocument();
        $query = new DomQuery();
        $query->query($doc, 'a /*comment');
    }

    public function testQueryAll1(): void
    {
        $rawHtml = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
        </head>
        <body>
            <div>
                <p>Hello, world!</p>
                <p>Goodbye, world!</p>
            </div>
        </body>
        </html>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $query = new DomQuery();
        $elements = \iterator_to_array($query->queryAll($doc, 'p'));
        self::assertCount(2, $elements);
        self::assertSame('Hello, world!', $elements[0]->textContent);
        self::assertSame('Goodbye, world!', $elements[1]->textContent);
    }

    public function testQuerAllyInvalidNodeType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scope node type.');

        $query = new DomQuery();
        \iterator_to_array($query->queryAll(new \DOMText('text'), 'p'));
    }

    public function testClosest(): void
    {
        $rawHtml = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test</title>
        </head>
        <body class="a">
            <div>
                <p>Hello, world!</p>
            </div>
        </body>
        </html>
        HTML;

        $doc = new \DOMDocument();
        $doc->loadHTML($rawHtml);
        $query = new DomQuery();
        $p = $doc->getElementsByTagName('p')[0];
        \assert($p instanceof \DOMElement);
        $ele = $query->closest($p, '.a');
        \assert($ele !== null);
        self::assertSame('body', $ele->tagName);

        $ele = $query->closest($p, 'p');
        self::assertSame($p, $ele);

        $ele = $query->closest($p, '.b');
        self::assertNull($ele);
    }
}
