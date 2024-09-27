<?php

declare(strict_types=1);

namespace Manychois\Cici\Matching;

use Manychois\Cici\Parsing\WqName;

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
     * Matches the type of an element.
     *
     * @param TNode  $element The element.
     * @param WqName $wqName  The qualified name of the type.
     *
     * @return bool `true` if the element matches the type; otherwise, `false`.
     */
    abstract public function matchElementType(object $element, WqName $wqName): bool;
}
