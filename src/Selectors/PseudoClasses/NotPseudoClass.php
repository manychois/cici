<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;
use Manychois\Cici\Selectors\AbstractSelector;

/**
 * Represents the `:not()` pseudo-class.
 */
class NotPseudoClass extends AbstractPseudoSelector
{
    public readonly AbstractSelector $selector;

    /**
     * Creates a new instance of the NotPseudoClass class.
     *
     * @param AbstractSelector $selector The complex real selector list to negate.
     */
    public function __construct(AbstractSelector $selector)
    {
        parent::__construct(true, 'not', true);

        $this->selector = $selector;
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    #[\Override]
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
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        return !$this->selector->matches($context, $target);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return \sprintf(':%s(%s)', $this->name, $this->selector->__toString());
    }


    #endregion extends AbstractPseudoSelector
}
