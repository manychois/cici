<?php

declare(strict_types=1);

namespace Manychois\Cici\Matching;

use Manychois\Cici\Parsing\WqName;

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
                    throw new \InvalidArgumentException(\sprintf('Namespace prefix not found: "%s".', $prefix));
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
        if ($wqName->prefixSpecified) {
            if ($wqName->prefix !== null) {
                if (!\array_key_exists($wqName->prefix, $this->nsLookup)) {
                    throw new \InvalidArgumentException(\sprintf('Namespace prefix not found: %s', $wqName->prefix));
                }
                $namespaceUri = $this->nsLookup[$wqName->prefix];
            }
        } else {
            if (!\array_key_exists('', $this->nsLookup)) {
                return $localName === '*' || $element->localName === $localName;
            }

            $namespaceUri = $this->nsLookup[''];
        }

        return $element->namespaceURI === $namespaceUri && ($localName === '*' || $element->localName === $localName);
    }

    #endregion extends AbstractMatchContext
}
