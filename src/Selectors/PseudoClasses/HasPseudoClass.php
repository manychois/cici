<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;
use Manychois\Cici\Selectors\AbstractSelector;

/**
 * Represents the `:has()` pseudo-class.
 */
class HasPseudoClass extends AbstractPseudoSelector
{
    public readonly AbstractSelector $selector;

    /**
     * Creates a new instance of the HasPseudoClass class.
     *
     * @param AbstractSelector $selector The relative selector list to match.
     */
    public function __construct(AbstractSelector $selector)
    {
        parent::__construct(true, 'has', true);

        $this->selector = $selector;
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'selector' => $this->selector,
            'type' => 'pseudo-class',
        ];
    }

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        return $this->selector->matches($context, $target);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return \sprintf(':%s(%s)', $this->name, $this->selector->__toString());
    }

    #endregion extends AbstractPseudoSelector
}
