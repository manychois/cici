<?php

declare(strict_types=1);

namespace Manychois\CiciBenchmark;

use PhpBench\Attributes\BeforeMethods;

#[BeforeMethods('setUp')]
class AbstractSelectorBench
{
    protected \DOMDocument $domDoc;

    /**
     * Iterates over all nodes in the DOM tree using a recursive generator.
     *
     * @param \DOMNode $node The root node of the DOM tree.
     *
     * @return \Generator<int,\DOMNode> All nodes in the DOM tree.
     */
    protected static function scanNode(\DOMNode $node): \Generator
    {
        foreach ($node->childNodes as $childNode) {
            yield $childNode;
            yield from self::scanNode($childNode);
        }
    }

    public function setUp(): void
    {
        $domDoc = new \DOMDocument();
        \libxml_use_internal_errors(true);
        $domDoc->loadHTMLFile(__DIR__ . '/sample.html');
        $this->domDoc = $domDoc;
    }
}
