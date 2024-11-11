<?php

declare(strict_types=1);

namespace Manychois\Cici;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Parsing\SelectorParser;
use Manychois\Cici\Selectors\AbstractSelector;
use Manychois\Cici\Tokenization\TextStream;
use Manychois\Cici\Tokenization\Tokenizer;

/**
 * Queries DOM nodes using CSS selectors.
 */
final readonly class DomQuery
{
    private Tokenizer $tokenizer;
    private SelectorParser $parser;

    /**
     * Creates a new instance of DomQuery.
     */
    public function __construct()
    {
        $this->tokenizer = new Tokenizer();
        $this->parser = new SelectorParser();
    }

    /**
     * Traverses the element and its parents (heading toward the document root) until it finds an element that matches
     * the specified CSS selector.
     *
     * @param \DOMElement          $element  The element to start traversing from.
     * @param string               $selector The CSS selector string.
     * @param array<string,string> $nsLookup The namespace lookup table.
     *
     * @return \DOMElement|null The first matching element, or null if no element matches the selector.
     */
    public function closest(\DOMElement $element, string $selector, array $nsLookup = []): ?\DOMElement
    {
        $root = $this->getRoot($element);
        $selectorList = $this->parseSelectorList($selector);
        $context = new DomNodeMatchContext($root, $element, $nsLookup);
        foreach ($context->loopAncestors($element, true) as $ancestor) {
            if ($ancestor instanceof \DOMElement && $selectorList->matches($context, $ancestor)) {
                return $ancestor;
            }
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) until it finds an element that matches the specified
     * CSS selector.
     *
     * @param \DOMNode             $scope    The node which its descendants will be queried.
     * @param string               $selector The CSS selector string.
     * @param array<string,string> $nsLookup The namespace lookup table.
     *
     * @return \DOMElement|null The first matching element, or null if no element matches the selector.
     */
    public function query(\DOMNode $scope, string $selector, array $nsLookup = []): ?\DOMElement
    {
        foreach ($this->queryAll($scope, $selector, $nsLookup) as $element) {
            return $element;
        }

        return null;
    }

    /**
     * Traverses the descendants of the node (in document order) and finds all elements that match the specified
     * CSS selector.
     *
     * @param \DOMNode             $scope    The node which its descendants will be queried.
     * @param string               $selector The CSS selector string.
     * @param array<string,string> $nsLookup The namespace lookup table.
     *
     * @return \Generator<int,\DOMElement> The generator that yields all matching elements.
     */
    public function queryAll(\DOMNode $scope, string $selector, array $nsLookup = []): \Generator
    {
        if (
            !($scope instanceof \DOMDocument)
            && !($scope instanceof \DOMDocumentFragment)
            && !($scope instanceof \DOMElement)
        ) {
            throw new \InvalidArgumentException('Invalid scope node type.');
        }

        $root = $this->getRoot($scope);
        $selectorList = $this->parseSelectorList($selector);
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);
        foreach ($context->loopDescendantElements($scope) as $child) {
            if (!$selectorList->matches($context, $child)) {
                continue;
            }

            yield $child;
        }
    }

    /**
     * Gets the root element of a DOM node.
     *
     * @param \DOMDocument|\DOMDocumentFragment|\DOMElement $node The node.
     *
     * @return \DOMElement|\DOMDocumentFragment|null The root element, if any.
     */
    private function getRoot(\DOMNode $node): \DOMElement|\DOMDocumentFragment|null
    {
        if ($node instanceof \DOMDocument) {
            return $node->documentElement;
        }
        if ($node instanceof \DOMDocumentFragment) {
            return $node;
        }

        return $node->ownerDocument?->documentElement;
    }

    /**
     * Parses a CSS selector string into a selector list.
     *
     * @param string $selector The CSS selector string.
     *
     * @return AbstractSelector The parsed selector list.
     */
    private function parseSelectorList(string $selector): AbstractSelector
    {
        $errors = new ParseExceptionCollection();
        $textStream = new TextStream($selector, $errors);
        $tokenStream = $this->tokenizer->convertToTokenStream($textStream, false);
        $selector = $this->parser->parseSelectorList($tokenStream);
        if ($errors->count() > 0) {
            throw $errors->get(0);
        }

        return $selector;
    }
}
