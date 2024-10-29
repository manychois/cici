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
     * Queries a DOM node using a CSS selector.
     *
     * @param \DOMNode             $scope    The node which its descendants will be queried.
     * @param string               $selector The CSS selector string.
     * @param array<string,string> $nsLookup The namespace lookup table.
     *
     * @return \DOMElement|null The first matching element, or null if no element matches the selector.
     */
    public function query(\DOMNode $scope, string $selector, array $nsLookup = []): ?\DOMElement
    {
        if ($scope instanceof \DOMDocument || $scope instanceof \DOMDocumentFragment || $scope instanceof \DOMElement) {
            $root = $this->getRoot($scope);
            $selectorList = $this->parseSelectorList($selector);
            $context = new DomNodeMatchContext($root, $scope, $nsLookup);
            foreach ($context->loopDescendants($scope, false) as $child) {
                if ($child instanceof \DOMElement && $selectorList->matches($context, $child)) {
                    return $child;
                }
            }

            return null;
        }

        throw new \InvalidArgumentException('Invalid scope node type.');
    }

    /**
     * Queries all DOM nodes using a CSS selector.
     *
     * @param \DOMNode             $scope    The node which its descendants will be queried.
     * @param string               $selector The CSS selector string.
     * @param array<string,string> $nsLookup The namespace lookup table.
     *
     * @return \Generator<int,\DOMNode> The generator that yields all matching elements.
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
        foreach ($context->loopDescendants($scope, false) as $child) {
            if (!($child instanceof \DOMElement) || !$selectorList->matches($context, $child)) {
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
