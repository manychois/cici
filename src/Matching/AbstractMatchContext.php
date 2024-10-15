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
     * @param TNode         $target The element.
     * @param string|WqName $wqName The qualified name of the attribute.
     *
     * @return string|null The attribute value, or `null` if the attribute does not exist.
     */
    abstract public function getAttributeValue(object $target, string|WqName $wqName): ?string;

    /**
     * Gets type of the node.
     *
     * @param TNode $target The node.
     *
     * @return NodeType The type of the node.
     */
    abstract public function getNodeType(object $target): NodeType;

    /**
     * Gets a group of radio buttons that have the same name attribute and are descendants of the same form.
     *
     * @param TNode $target One of the radio buttons in the group.
     *
     * @return array<int,TNode> The radio buttons in the group, including the target.
     */
    abstract public function getRadioButtonGroup(object $target): array;

    /**
     * Checks whether a node is actually a disabled element.
     *
     * @param TNode $target The node to check.
     *
     * @return bool `true` if the node is actually a disabled element; otherwise, `false`.
     */
    abstract public function isActuallyDisabled(object $target): bool;

    /**
     * Checks if a node is an HTML element with one of the specified local names.
     *
     * @param TNode  $target        The node to check.
     * @param string ...$localNames The local names to match. If empty, only the namespace URI is checked.
     *
     * @return bool `true` if the node is an HTML element with one of the specified local names; otherwise, `false`.
     */
    abstract public function isHtmlElement(object $target, string ...$localNames): bool;

    /**
     * Checks if an node is a read-writable element.
     *
     * @param TNode $target The node to check.
     *
     * @return bool `true` if the node is a read-writable element; otherwise, `false`.
     */
    abstract public function isReadWritable(object $target): bool;

    /**
     * Loops through all the ancestors of a node.
     *
     * @param TNode $target      The node to start from.
     * @param bool  $includeSelf Whether to include the target node itself.
     *
     * @return \Generator<int,TNode> All the ancestors.
     */
    abstract public function loopAncestors(object $target, bool $includeSelf): \Generator;

    /**
     * Loops through all the children elements of a node.
     *
     * @param TNode $target The node to start from.
     *
     * @return \Generator<int,TNode> All the children elements.
     */
    abstract public function loopChildren(object $target): \Generator;

    /**
     * Loops through all the descendant nodes of a node.
     *
     * @param TNode $target      The node to start from.
     * @param bool  $includeSelf Whether to include the target node itself.
     *
     * @return \Generator<int,TNode> All the descendant nodes.
     */
    abstract public function loopDescendants(object $target, bool $includeSelf): \Generator;

    /**
     * Loops through all the possible nodes that can be on the left side of a combinator.
     *
     * @param TNode      $target     The node on the right side of the combinator.
     * @param Combinator $combinator The combinator.
     *
     * @return \Generator<int,TNode> All the possible nodes that can be on the left side of a combinator.
     */
    abstract public function loopLeftCandidates(object $target, Combinator $combinator): \Generator;

    /**
     * Loops through all the possible elements that can be on the right side of a combinator.
     *
     * @param TNode      $target     The node on the left side of the combinator.
     * @param Combinator $combinator The combinator.
     *
     * @return \Generator<int,TNode> All the possible elements that can be on the right side of a combinator.
     */
    abstract public function loopRightCandidates(object $target, Combinator $combinator): \Generator;

    /**
     * Matches the element type of a node.
     *
     * @param TNode  $target The node to match.
     * @param WqName $wqName The qualified name of the type.
     *
     * @return bool `true` if the node matches the element type; otherwise, `false`.
     */
    abstract public function matchElementType(object $target, WqName $wqName): bool;

    /**
     * Checks if a node matches the default namespace declared in the scope.
     * If the default namespace is not declared, the node is considered to match.
     *
     * @param TNode $target The node to match.
     *
     * @return bool `true` if the node matches the default namespace; otherwise, `false`.
     */
    abstract public function matchDefaultNamespace(object $target): bool;
}
