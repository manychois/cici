<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Matching\AnbNotation;
use Manychois\Cici\Selectors\AbstractPseudoSelector;

/**
 * Represents a typed child-indexed pseudo-class selector, i.e. `:nth-of-type()`, `:nth-last-of-type()`,
 * `:first-of-type`, `:last-of-type` and `:only-of-type`.
 */
class TypedChildIndexedPseudoClass extends AbstractPseudoSelector
{
    public readonly ?AnbNotation $anb;

    /**
     * Creates a new instance of the TypedChildIndexedPseudoClass class.
     *
     * @param string           $name The name of the pseudo-class.
     * @param AnbNotation|null $anb  The `an+b` notation.
     */
    public function __construct(string $name, ?AnbNotation $anb)
    {
        parent::__construct(true, $name, $anb !== null);

        $this->anb = $anb;
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        $parent = $context->getParentNode($target);
        if ($parent === null) {
            return false;
        }

        $siblings = [];
        foreach ($context->loopChildren($parent) as $child) {
            if (!$context->areOfSameElementType($child, $target)) {
                continue;
            }

            $siblings[] = $child;
        }
        $index = \array_search($target, $siblings, true);
        \assert(\is_int($index));

        if ($this->name === 'first-of-type') {
            return $index === 0;
        }

        $count = \count($siblings);
        if ($this->name === 'last-of-type') {
            return $index === $count - 1;
        }
        if ($this->name === 'only-of-type') {
            return $index === 0 && $count === 1;
        }

        $index = match ($this->name) {
            'nth-of-type' => $index + 1,
            'nth-last-of-type' => $count - $index,
            default => 0,
        };

        \assert($this->anb !== null);

        return $this->anb->matches($index);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        /** @var array<string,mixed> $json */
        $json = parent::jsonSerialize();
        if (!$this->isFunctional) {
            return $json;
        }
        \assert($this->anb !== null);
        $json['formula'] = $this->anb;
        unset($json['args']);
        \ksort($json);

        return $json;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if (!$this->isFunctional) {
            return parent::__toString();
        }
        \assert($this->anb !== null);
        $args = $this->anb->__toString();

        return \sprintf(':%s(%s)', $this->name, $args);
    }

    #endregion extends AbstractPseudoSelector
}
