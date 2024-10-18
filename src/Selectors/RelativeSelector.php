<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;

/**
 * Represents a relative selector.
 */
class RelativeSelector extends AbstractSelector
{
    public readonly Combinator $combinator;
    public readonly AbstractSelector $selector;

    /**
     * Creates a new instance of RelativeSelector.
     *
     * @param Combinator       $combinator The combinator.
     * @param AbstractSelector $selector   The selector.
     */
    public function __construct(Combinator $combinator, AbstractSelector $selector)
    {
        $this->combinator = $combinator;
        $this->selector = $selector;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'combinator' => $this->combinator,
            'selector' => $this->selector,
            'type' => 'relative',
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        foreach ($context->loopRightCandidates($target, $this->combinator) as $candidate) {
            if ($this->selector->matches($context, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return \ltrim($this->combinator->value . $this->selector->__toString());
    }

    #endregion extends AbstractSelector
}
