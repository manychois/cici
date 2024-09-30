<?php

declare(strict_types=1);

namespace Manychois\Cici\Matching;

use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Selectors\Combinator;

/**
 * Represents all information needed to match a selector against a DOM tree.
 *
 * @template TNode of object
 */
abstract class AbstractMatchContext
{
    /**
     * @var TNode
     */
    public readonly object $root;
    /**
     * @var TNode
     */
    public readonly object $scope;
    /**
     * @var array<string,string>
     */
    protected readonly array $nsLookup;

    /**
     * @param TNode                $root     The root node of the DOM tree.
     * @param TNode                $scope    The node to start matching from.
     * @param array<string,string> $nsLookup The namespace lookup table.
     */
    public function __construct(object $root, object $scope, array $nsLookup)
    {
        $this->root = $root;
        $this->scope = $scope;
        $this->nsLookup = $nsLookup;
    }

    /**
     * Gets the attribute value of an element.
     *
     * @param TNode         $element The element.
     * @param string|WqName $wqName  The qualified name of the attribute.
     *
     * @return string|null The attribute value, or `null` if the attribute does not exist.
     */
    abstract public function getAttributeValue(object $element, string|WqName $wqName): ?string;

    /**
     * Loops through all the children of an element.
     *
     * @param TNode $parentNode The parent node.
     *
     * @return \Generator<int,TNode> All the children.
     */
    abstract public function loopChildren(object $parentNode): \Generator;

    /**
     * Loops through all the descendant elements of an element.
     *
     * @param TNode $parentNode  The parent node.
     * @param bool  $includeSelf `true` to include the parent node itself if it is an element;
     *                           otherwise, `false`.
     *
     * @return \Generator<int,TNode> All the descendant elements.
     */
    abstract public function loopDescendants(object $parentNode, bool $includeSelf): \Generator;

    /**
     * Loops through all the possible elements that can be on the left side of a combinator.
     *
     * @param TNode      $element    The element on the right side of the combinator.
     * @param Combinator $combinator The combinator.
     *
     * @return \Generator<int,TNode> All the possible elements that can be on the left side of a combinator.
     */
    abstract public function loopLeftCandidates(object $element, Combinator $combinator): \Generator;

    /**
     * Loops through all the possible elements that can be on the right side of a combinator.
     *
     * @param TNode      $node       The node on the left side of the combinator.
     * @param Combinator $combinator The combinator.
     *
     * @return \Generator<int,TNode> All the possible elements that can be on the right side of a combinator.
     */
    abstract public function loopRightCandidates(object $node, Combinator $combinator): \Generator;

    /**
     * Matches the type of an element.
     *
     * @param TNode  $element The element.
     * @param WqName $wqName  The qualified name of the type.
     *
     * @return bool `true` if the element matches the type; otherwise, `false`.
     */
    abstract public function matchElementType(object $element, WqName $wqName): bool;

    /**
     * Checks if an element matches the default namespace declared in the scope.
     * If the default namespace is not declared, the element is considered to match.
     *
     * @param TNode $element The element.
     *
     * @return bool `true` if the element matches the default namespace; otherwise, `false`.
     */
    abstract public function matchDefaultNamespace(object $element): bool;
}
