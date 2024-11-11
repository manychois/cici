<?php

declare(strict_types=1);

namespace Manychois\CiciBenchmark;

use Manychois\Cici\DomQuery;
use RuntimeException;
use Symfony\Component\CssSelector\CssSelectorConverter;

class NthChildSelector extends AbstractSelectorBench
{
    private string $requestCssSelector;
    private string $expectedResult;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestCssSelector = 'dd:nth-child(3n+1)';
        $this->expectedResult = \implode(',', [
            'Variables',
            'Operators',
            'Classes and Objects',
            'Errors',
            'Generators',
            'Predefined Variables',
            'Predefined Attributes',
            'Installed as CGI binary',
            'Filesystem Security',
            'User Submitted Data',
            'Sessions',
            'Connection handling',
            'Garbage Collection',
            'Authentication Services',
            'Cryptography Extensions',
            'File System Related Extensions',
            'Mail Related Extensions',
            'Process Control Extensions',
            'Search Engine Extensions',
            'Text Processing',
            'Windows Only Extensions',
            'Previous menu item',
            'Scroll to bottom',
            'Goto search(current page)',
        ]);
    }

    public function benchManychoisCici(): void
    {
        $domQuery = new DomQuery();
        $actual = [];
        $elements = $domQuery->queryAll($this->domDoc, $this->requestCssSelector);
        foreach ($elements as $ele) {
            $actual[] = $ele->textContent;
        }
        $actual = \implode(',', $actual);

        if ($actual !== $this->expectedResult) {
            throw new RuntimeException('Unexpected result');
        }
    }

    public function benchSymfonyCssSelector(): void
    {
        $domXPath = new \DOMXPath($this->domDoc);
        $converter = new CssSelectorConverter();
        $xpath = $converter->toXPath($this->requestCssSelector);
        $nodeList = $domXPath->query($xpath);
        \assert($nodeList !== false);
        $actual = [];
        foreach ($nodeList as $node) {
            $actual[] = $node->textContent;
        }
        $actual = \implode(',', $actual);

        if ($actual !== $this->expectedResult) {
            throw new RuntimeException('Unexpected result');
        }
    }
}
