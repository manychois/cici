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
        $this->assertSame('Hello, world!', $ele->textContent);

        $head = $query->query($doc, 'head');
        $this->assertNotNull($head);

        $body = $query->query($doc, 'body');
        $this->assertNotNull($body);

        $title = $query->query($head, 'title');
        $this->assertSame('Test', $title->textContent);

        $title = $query->query($body, 'title');
        $this->assertNull($title);
    }

    public function testQuery2(): void
    {
        $doc = new \DOMDocument();
        $fragment = $doc->createDocumentFragment();
        $fragment->append($doc->createElement('p', 'Hello, world!'));
        $query = new DomQuery();
        $ele = $query->query($fragment, ':scope > p');
        $this->assertSame('Hello, world!', $ele->textContent);
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
        $this->assertCount(2, $elements);
        $this->assertSame('Hello, world!', $elements[0]->textContent);
        $this->assertSame('Goodbye, world!', $elements[1]->textContent);
    }

    public function testQuerAllyInvalidNodeType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid scope node type.');

        $query = new DomQuery();
        \iterator_to_array($query->queryAll(new \DOMText('text'), 'p'));
    }
}
