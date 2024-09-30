<?php

declare(strict_types=1);

namespace Manychois\Cici\Matching;

use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\Combinator;

/**
 * Represents a match context for native PHP DOM nodes.
 *
 * @template-extends AbstractMatchContext<\DOMNode>
 */
class DomNodeMatchContext extends AbstractMatchContext
{
    /**
     * Creates a new instance of the match context.
     *
     * @param \DOMNode             $root     The root node of the DOM tree.
     * @param \DOMNode             $scope    The node to start matching from.
     * @param array<string,string> $nsLookup The namespace lookup table.
     */
    public function __construct(\DOMNode $root, \DOMNode $scope, array $nsLookup)
    {
        parent::__construct($root, $scope, $nsLookup);
    }

    #region extends AbstractMatchContext

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMElement>
     */
    public function loopChildren(object $parentNode): \Generator
    {
        \assert($parentNode instanceof \DOMDocument ||
            $parentNode instanceof \DOMDocumentFragment ||
        $parentNode instanceof \DOMElement);

        foreach ($parentNode->childNodes as $child) {
            if (!($child instanceof \DOMElement)) {
                continue;
            }

            yield $child;
        }
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMElement>
     */
    public function loopDescendants(object $parentNode, bool $includeSelf): \Generator
    {
        \assert($parentNode instanceof \DOMDocument ||
            $parentNode instanceof \DOMDocumentFragment ||
        $parentNode instanceof \DOMElement);

        if ($includeSelf && $parentNode instanceof \DOMElement) {
            $elements = [$parentNode];
        } else {
            /**
             * @var array<\DOMElement> $elements
             */
            $elements = \iterator_to_array($this->loopChildren($parentNode));
        }

        while (\count($elements) > 0) {
            $element = \array_shift($elements);
            \assert($element instanceof \DOMElement);

            yield $element;

            /**
             * @var array<\DOMElement> $children
             */
            $children = \iterator_to_array($this->loopChildren($element));
            \array_splice($elements, 0, 0, $children);
        }
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMElement>
     */
    public function loopLeftCandidates(object $element, Combinator $combinator): \Generator
    {
        \assert($element instanceof \DOMElement);
        if ($combinator === Combinator::Descendant) {
            $current = $element->parentElement;
            while ($current !== null) {
                yield $current;

                $current = $current->parentElement;
            }
        } elseif ($combinator === Combinator::Child) {
            $current = $element->parentElement;
            if ($current !== null) {
                yield $current;
            }
        } elseif ($combinator === Combinator::NextSibling) {
            $current = $element->previousElementSibling;
            if ($current !== null) {
                yield $current;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            $current = $element->previousElementSibling;
            while ($current !== null) {
                yield $current;

                $current = $current->previousElementSibling;
            }
        } else {
            throw new \RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMElement>
     */
    public function loopRightCandidates(object $node, Combinator $combinator): \Generator
    {
        if ($combinator === Combinator::Descendant) {
            yield from $this->loopDescendants($node, false);
        } elseif ($combinator === Combinator::Child) {
            yield from $this->loopChildren($node);
        } elseif ($combinator === Combinator::NextSibling) {
            if ($node instanceof \DOMElement && $node->nextElementSibling !== null) {
                yield $node->nextElementSibling;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            if ($node instanceof \DOMElement) {
                $current = $node->nextElementSibling;
                while ($current !== null) {
                    yield $current;

                    $current = $current->nextElementSibling;
                }
            }
        } else {
            throw new \RuntimeException(\sprintf('Unsupported combinator "%s".', $combinator->value));
        }
    }

    /**
     * @inheritDoc
     */
    public function getAttributeValue(object $element, string|WqName $wqName): ?string
    {
        \assert($element instanceof \DOMElement);
        $prefix = \is_string($wqName) ? null : $wqName->prefix;
        $localName = \is_string($wqName) ? $wqName : $wqName->localName;
        $attr = null;
        if ($prefix === null) {
            if ($element->hasAttribute($localName)) {
                $attr = $element->getAttributeNode($localName);
            }
        } else {
            if ($prefix === '*') {
                foreach ($element->attributes as $candidate) {
                    \assert($candidate instanceof \DOMAttr);
                    if ($candidate->localName === $localName) {
                        $attr = $candidate;

                        break;
                    }
                }
            } else {
                if (!\array_key_exists($prefix, $this->nsLookup)) {
                    throw new \RuntimeException(\sprintf('Namespace prefix not found: "%s".', $prefix));
                }
                $namespaceUri = $this->nsLookup[$prefix];
                foreach ($element->attributes as $candidate) {
                    \assert($candidate instanceof \DOMAttr);
                    if ($candidate->namespaceURI === $namespaceUri && $candidate->localName === $localName) {
                        $attr = $candidate;

                        break;
                    }
                }
            }
        }

        return $attr?->value;
    }

    /**
     * @inheritDoc
     */
    public function matchElementType(object $element, WqName $wqName): bool
    {
        \assert($element instanceof \DOMElement);
        $namespaceUri = null;
        $localName = $wqName->localName;
        $isLocalNameMatched = $localName === '*' || $element->localName === $localName;
        if ($wqName->prefixSpecified) {
            if ($wqName->prefix !== null) {
                if ($wqName->prefix === '*') {
                    return $isLocalNameMatched;
                }
                if (!\array_key_exists($wqName->prefix, $this->nsLookup)) {
                    throw new \RuntimeException(\sprintf('Namespace prefix not found: "%s".', $wqName->prefix));
                }
                $namespaceUri = $this->nsLookup[$wqName->prefix];
            }
        } else {
            if (!\array_key_exists('', $this->nsLookup)) {
                return $isLocalNameMatched;
            }

            $namespaceUri = $this->nsLookup[''];
        }

        return $element->namespaceURI === $namespaceUri && $isLocalNameMatched;
    }

    /**
     * @inheritDoc
     */
    public function matchDefaultNamespace(object $element): bool
    {
        \assert($element instanceof \DOMElement);
        if (\array_key_exists('', $this->nsLookup)) {
            return $this->nsLookup[''] === $element->namespaceURI;
        }

        return true;
    }
    #endregion extends AbstractMatchContext
}
