<?php

declare(strict_types=1);

namespace Manychois\Cici\Matching;

use Generator;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\Combinator;

/**
 * Represents a match context for native PHP DOM nodes.
 *
 * @template-extends AbstractMatchContext<\DOMNode>
 */
class DomNodeMatchContext extends AbstractMatchContext
{
    private const NS_HTML = 'http://www.w3.org/1999/xhtml';

    /**
     * Creates a new instance of the match context.
     *
     * @param \DOMNode|null        $root     The root node of the DOM tree, if any.
     * @param \DOMNode             $scope    The node which its descendants will be matched.
     * @param array<string,string> $nsLookup The namespace lookup table.
     */
    public function __construct(?\DOMNode $root, \DOMNode $scope, array $nsLookup)
    {
        parent::__construct($root, $scope, $nsLookup);
    }

    #region extends AbstractMatchContext

    /**
     * @inheritDoc
     */
    #[\Override]
    public function areOfSameElementType(object $node1, object $node2): bool
    {
        return $node1 instanceof \DOMElement &&
            $node2 instanceof \DOMElement &&
            $node1->namespaceURI === $node2->namespaceURI &&
            $node1->localName === $node2->localName;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getAttributeValue(object $target, string|WqName $wqName): ?string
    {
        if (!($target instanceof \DOMElement)) {
            return null;
        }

        $prefix = \is_string($wqName) ? null : $wqName->prefix;
        $localName = \is_string($wqName) ? $wqName : $wqName->localName;
        $attr = null;
        if ($prefix === null) {
            if ($target->hasAttribute($localName)) {
                $attr = $target->getAttributeNode($localName);
            }
        } else {
            if ($prefix === '*') {
                foreach ($target->attributes as $candidate) {
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
                foreach ($target->attributes as $candidate) {
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
    #[\Override]
    public function getNodeType(object $target): NodeType
    {
        return match ($target->nodeType) {
            \XML_ELEMENT_NODE => NodeType::Element,
            \XML_TEXT_NODE => NodeType::Text,
            \XML_COMMENT_NODE => NodeType::Comment,
            \XML_DOCUMENT_NODE, \XML_HTML_DOCUMENT_NODE => NodeType::Document,
            \XML_DOCUMENT_TYPE_NODE => NodeType::DocumentType,
            \XML_DOCUMENT_FRAG_NODE => NodeType::DocumentFragment,
            default => NodeType::Unsupported,
        };
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getParentNode(object $target): ?object
    {
        return $target->parentNode;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getRadioButtonGroup(object $target): array
    {
        \assert($target instanceof \DOMElement);
        $name = $target->getAttribute('name');
        if ($name === '') {
            return [$target];
        }

        $topmost = null;
        $owner = null;
        foreach ($this->loopAncestors($target, false) as $pNode) {
            if ($owner === null && $this->isHtmlElement($pNode, 'form')) {
                $owner = $pNode;
            }
            $topmost = $pNode;
        }

        if ($owner === null) {
            if ($topmost === null) {
                return [$target];
            }
            $owner = $topmost;
        }

        $group = [];
        foreach ($this->loopDescendants($owner, false) as $node) {
            if (!$this->isHtmlElement($node, 'input')) {
                continue;
            }

            \assert($node instanceof \DOMElement);
            if ($node->getAttribute('name') !== $name || $node->getAttribute('type') !== 'radio') {
                continue;
            }

            $group[] = $node;
        }

        return $group;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function isActuallyDisabled(object $target): bool
    {
        if (!$this->isHtmlElement($target)) {
            return false;
        }
        \assert($target instanceof \DOMElement);
        if (\in_array($target->localName, ['button', 'input', 'select', 'textarea', 'fieldset'], true)) {
            if ($target->hasAttribute('disabled')) {
                return true;
            }

            $fieldset = $this->firstAncestorHtmlElement(
                $target,
                false,
                function (\DOMElement $node) use ($target): bool {
                    if ($node->localName !== 'fieldset') {
                        return false;
                    }
                    if (!$node->hasAttribute('disabled')) {
                        return false;
                    }

                    foreach ($this->loopChildren($node) as $child) {
                        if ($this->isHtmlElement($child, 'legend')) {
                            return $this->firstDescendantHtmlElement(
                                $child,
                                false,
                                static fn ($e): bool => $e === $target
                            ) === null;
                        }
                    }

                    return true;
                }
            );
            if ($fieldset !== null) {
                return true;
            }
        }

        if ($target->localName === 'optgroup' && $target->hasAttribute('disabled')) {
            return true;
        }

        if ($target->localName === 'option') {
            if ($target->hasAttribute('disabled')) {
                return true;
            }

            $parent = $target->parentNode;
            if ($parent !== null && $this->isHtmlElement($parent, 'optgroup')) {
                \assert($parent instanceof \DOMElement);
                if ($parent->hasAttribute('disabled')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function isHtmlElement(object $target, string ...$localNames): bool
    {
        if ($target instanceof \DOMElement) {
            if ($target->namespaceURI === null || $target->namespaceURI === self::NS_HTML) {
                return \count($localNames) === 0 || \in_array($target->localName, $localNames, true);
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function isReadWritable(object $target): bool
    {
        $readWrite = false;
        if ($this->isHtmlElement($target, 'input')) {
            \assert($target instanceof \DOMElement);
            $type = $target->getAttribute('type');
            if (!\in_array($type, ['checkbox', 'color', 'file', 'hidden', 'radio', 'range'], true)) {
                if ($this->getAttributeValue($target, 'readonly') === null) {
                    $readWrite = !$this->isActuallyDisabled($target);
                }
            }
        } elseif ($this->isHtmlElement($target, 'textarea')) {
            if ($this->getAttributeValue($target, 'readonly') === null) {
                $readWrite = !$this->isActuallyDisabled($target);
            }
        } elseif ($this->isHtmlElement($target)) {
            \assert($target instanceof \DOMElement);
            $isContentEditable = function (\DOMElement $ele): bool {
                $value = $this->getAttributeValue($ele, 'contenteditable');

                return $value !== null && $value !== 'false';
            };
            $readWrite = $this->firstAncestorHtmlElement($target, true, $isContentEditable) !== null;
        }

        return $readWrite;
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMElement|\DOMDocument|\DOMDocumentFragment>
     */
    #[\Override]
    public function loopAncestors(object $target, bool $includeSelf): Generator
    {
        if ($includeSelf) {
            if (
                $target instanceof \DOMElement || $target instanceof \DOMDocument ||
                $target instanceof \DOMDocumentFragment
            ) {
                yield $target;
            }
        }
        $parent = $target->parentNode;
        while ($parent !== null) {
            \assert($parent instanceof \DOMElement || $parent instanceof \DOMDocument ||
            $parent instanceof \DOMDocumentFragment);

            yield $parent;

            $parent = $parent->parentNode;
        }
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMElement>
     */
    #[\Override]
    public function loopChildren(object $target): \Generator
    {
        foreach ($target->childNodes as $child) {
            if (!($child instanceof \DOMElement)) {
                continue;
            }

            yield $child;
        }
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMNode>
     */
    #[\Override]
    public function loopDescendants(object $target, bool $includeSelf): \Generator
    {
        if ($includeSelf) {
            yield $target;
        }

        /**
         * @var array<int,\DOMNode> $nodes
         */
        $nodes = \iterator_to_array($target->childNodes);
        while (\count($nodes) > 0) {
            $node = \array_shift($nodes);

            yield $node;

            /**
             * @var array<int,\DOMNode> $subNodes
             */
            $subNodes = \iterator_to_array($node->childNodes);
            \array_splice($nodes, 0, 0, $subNodes);
        }
    }

    /**
     * @inheritDoc
     *
     * @return \Generator<int,\DOMElement|\DOMDocumentFragment>
     */
    #[\Override]
    public function loopLeftCandidates(object $target, Combinator $combinator): \Generator
    {
        \assert($target instanceof \DOMElement);
        if ($combinator === Combinator::Descendant) {
            foreach ($this->loopAncestors($target, false) as $node) {
                if (!($node instanceof \DOMElement) && !($node instanceof \DOMDocumentFragment)) {
                    continue;
                }

                yield $node;
            }
        } elseif ($combinator === Combinator::Child) {
            $parent = $target->parentNode;
            if ($parent instanceof \DOMElement || $parent instanceof \DOMDocumentFragment) {
                yield $parent;
            }
        } elseif ($combinator === Combinator::NextSibling) {
            $prev = $target->previousElementSibling;
            if ($prev !== null) {
                yield $prev;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            $prev = $target->previousElementSibling;
            while ($prev !== null) {
                yield $prev;

                $prev = $prev->previousElementSibling;
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
    #[\Override]
    public function loopRightCandidates(object $target, Combinator $combinator): \Generator
    {
        if ($combinator === Combinator::Descendant) {
            foreach ($this->loopDescendants($target, false) as $child) {
                if (!($child instanceof \DOMElement)) {
                    continue;
                }

                yield $child;
            }
        } elseif ($combinator === Combinator::Child) {
            yield from $this->loopChildren($target);
        } elseif ($combinator === Combinator::NextSibling) {
            if ($target instanceof \DOMElement && $target->nextElementSibling !== null) {
                yield $target->nextElementSibling;
            }
        } elseif ($combinator === Combinator::SubsequentSibling) {
            if ($target instanceof \DOMElement) {
                $current = $target->nextElementSibling;
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
    #[\Override]
    public function matchElementType(object $target, WqName $wqName): bool
    {
        if (!($target instanceof \DOMElement)) {
            return false;
        }

        $namespaceUri = null;
        $localName = $wqName->localName;
        $isLocalNameMatched = $localName === '*' || $target->localName === $localName;
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

        return $target->namespaceURI === $namespaceUri && $isLocalNameMatched;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matchDefaultNamespace(object $target): bool
    {
        if (\array_key_exists('', $this->nsLookup)) {
            return $this->nsLookup[''] === $target->namespaceURI;
        }

        return true;
    }

    #endregion extends AbstractMatchContext

    /**
     * Gets the first ancestor HTML element that matches the specified predicate.
     *
     * @param \DOMNode $target      The node to start from.
     * @param bool     $includeSelf Whether to include the target node itself.
     * @param callable $predicate   The predicate to match.
     *
     * @return \DOMElement|null The first ancestor HTML element that matches the predicate, or `null` if not found.
     *
     * @phpstan-param callable(\DOMElement):bool $predicate
     */
    private function firstAncestorHtmlElement(\DOMNode $target, bool $includeSelf, callable $predicate): ?\DOMElement
    {
        foreach ($this->loopAncestors($target, $includeSelf) as $node) {
            if (!$this->isHtmlElement($node)) {
                continue;
            }

            \assert($node instanceof \DOMElement);
            if ($predicate($node)) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Gets the first descendant HTML element that matches the specified predicate.
     *
     * @param \DOMNode $target      The element to start from.
     * @param bool     $includeSelf Whether to include the target node itself.
     * @param callable $predicate   The predicate to match.
     *
     * @return \DOMElement|null The first descendant HTML element that matches the predicate, or `null` if not found.
     *
     * @phpstan-param callable(\DOMElement):bool $predicate
     */
    private function firstDescendantHtmlElement(\DOMNode $target, bool $includeSelf, callable $predicate): ?\DOMElement
    {
        foreach ($this->loopDescendants($target, $includeSelf) as $node) {
            if (!$this->isHtmlElement($node)) {
                continue;
            }

            \assert($node instanceof \DOMElement);
            if ($predicate($node)) {
                return $node;
            }
        }

        return null;
    }
}
