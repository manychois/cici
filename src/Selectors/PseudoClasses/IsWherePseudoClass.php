<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;
use Manychois\Cici\Selectors\ForgivingSelectorList;

/**
 * Represents the `:is()` or `:where()` pseudo-class.
 */
class IsWherePseudoClass extends AbstractPseudoSelector
{
    public readonly ForgivingSelectorList $selector;

    /**
     * Creates a new instance of the IsPseudoClass class.
     *
     * @param string                $name     The name of the pseudo-class.
     * @param ForgivingSelectorList $selector The forgiving selector list to match.
     */
    public function __construct(string $name, ForgivingSelectorList $selector)
    {
        \assert($name === 'is' || $name === 'where');

        parent::__construct(true, $name, true);

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
        return $this->selector->matches($context, $target);
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
