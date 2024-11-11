<?php

declare(strict_types=1);

namespace Manychois\CiciBenchmark;

use Manychois\Cici\DomQuery;
use RuntimeException;
use Symfony\Component\CssSelector\CssSelectorConverter;

class IdSelector extends AbstractSelectorBench
{
    /**
     * @var array<string,\DOMElement>
     */
    private array $idMap;

    public function setUp(): void
    {
        parent::setUp();

        $idMap = [];
        foreach (self::scanNode($this->domDoc) as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            if (!$node->hasAttribute('id')) {
                continue;
            }

            $id = $node->getAttribute('id');
            if (\str_starts_with($id, '2024')) {
                // skip bad ID selectors
                continue;
            }
            $idMap[$id] = $node;
        }

        $this->idMap = $idMap;
    }

    public function benchGetElementById(): void
    {
        foreach ($this->idMap as $id => $expected) {
            $actual = $this->domDoc->getElementById($id);
            if ($actual !== $expected) {
                throw new \RuntimeException('Unexpected result');
            }
        }
    }

    public function benchManychoisCici(): void
    {
        $domQuery = new DomQuery();
        foreach ($this->idMap as $id => $expected) {
            $actual = $domQuery->query($this->domDoc, '#' . $id);
            if ($actual !== $expected) {
                throw new RuntimeException('Unexpected result');
            }
        }
    }

    public function benchSymfonyCssSelector(): void
    {
        $domXPath = new \DOMXPath($this->domDoc);
        $converter = new CssSelectorConverter();
        foreach ($this->idMap as $id => $expected) {
            $xpath = $converter->toXPath('#' . $id);
            $result = $domXPath->query($xpath);
            \assert($result !== false);
            $actual = $result->item(0);
            if ($actual !== $expected) {
                throw new RuntimeException('Unexpected result');
            }
        }
    }
}
