<?php

declare(strict_types=1);

namespace Manychois\CiciBenchmark;

use Manychois\Cici\DomQuery;
use RuntimeException;
use Symfony\Component\CssSelector\CssSelectorConverter;

class ClassSelector extends AbstractSelectorBench
{
    /**
     * @var array<string,\DOMElement>
     */
    private array $classMap;

    public function setUp(): void
    {
        parent::setUp();

        $classMap = [];
        foreach (self::scanNode($this->domDoc) as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            if (!$node->hasAttribute('class')) {
                continue;
            }

            $className = $node->getAttribute('class');
            $classNames = \preg_split('/\s+/', $className, -1, \PREG_SPLIT_NO_EMPTY);
            \assert($classNames !== false);
            \sort($classNames);
            $css = '.' . \implode('.', $classNames);
            foreach (\array_keys($classMap) as $existingCss) {
                if (\str_starts_with($existingCss, $css)) {
                    continue 2;
                }
            }
            $classMap[$css] = $node;
        }

        $this->classMap = $classMap;
    }

    public function benchManychoisCici(): void
    {
        $domQuery = new DomQuery();
        foreach ($this->classMap as $className => $expected) {
            $actual = $domQuery->query($this->domDoc, $className);
            if ($actual !== $expected) {
                throw new RuntimeException('Unexpected result');
            }
        }
    }

    public function benchSymfonyCssSelector(): void
    {
        $domXPath = new \DOMXPath($this->domDoc);
        $converter = new CssSelectorConverter();
        foreach ($this->classMap as $className => $expected) {
            $xpath = $converter->toXPath($className);
            $result = $domXPath->query($xpath);
            \assert($result !== false);
            $actual = $result->item(0);
            if ($actual !== $expected) {
                throw new RuntimeException('Unexpected result');
            }
        }
    }
}
