<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;

/**
 * Represents a selector.
 */
abstract class AbstractSelector implements \JsonSerializable, \Stringable
{
    /**
     * Determines whether the selector matches the target.
     *
     * @template TNode of object
     *
     * @param AbstractMatchContext<TNode> $context The matching context.
     * @param TNode                       $target  The target to match.
     *
     * @return bool Whether the selector matches the target.
     */
    abstract public function matches(AbstractMatchContext $context, object $target): bool;
}
