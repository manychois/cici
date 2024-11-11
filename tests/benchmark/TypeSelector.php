<?php

declare(strict_types=1);

namespace Manychois\CiciBenchmark;

use Manychois\Cici\DomQuery;
use RuntimeException;
use Symfony\Component\CssSelector\CssSelectorConverter;

class TypeSelector extends AbstractSelectorBench
{
    /**
     * @var array<string,\DOMElement>
     */
    private array $typeMap;

    public function setUp(): void
    {
        parent::setUp();

        $typeMap = [];
        foreach (self::scanNode($this->domDoc) as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }
            if (\array_key_exists($node->tagName, $typeMap)) {
                continue;
            }
            $typeMap[$node->tagName] = $node;
        }

        $this->typeMap = $typeMap;
    }

    public function benchGetElementByTagName(): void
    {
        foreach ($this->typeMap as $typeName => $expected) {
            $actual = $this->domDoc->getElementsByTagName($typeName)[0];
            if ($actual !== $expected) {
                throw new \RuntimeException('Unexpected result');
            }
        }
    }

    public function benchManychoisCici(): void
    {
        $domQuery = new DomQuery();
        foreach ($this->typeMap as $typeName => $expected) {
            $actual = $domQuery->query($this->domDoc, $typeName);
            if ($actual !== $expected) {
                throw new RuntimeException('Unexpected result');
            }
        }
    }

    public function benchSymfonyCssSelector(): void
    {
        $domXPath = new \DOMXPath($this->domDoc);
        $converter = new CssSelectorConverter();
        foreach ($this->typeMap as $typeName => $expected) {
            $xpath = $converter->toXPath($typeName);
            $result = $domXPath->query($xpath);
            \assert($result !== false);
            $actual = $result->item(0);
            if ($actual !== $expected) {
                throw new RuntimeException('Unexpected result');
            }
        }
    }
}
