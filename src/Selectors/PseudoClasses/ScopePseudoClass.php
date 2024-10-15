<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;

/**
 * Represents the `:scope` pseudo-class.
 */
class ScopePseudoClass extends AbstractPseudoSelector
{
    /**
     * Creates a new instance of the ScopePseudoClass class.
     */
    public function __construct()
    {
        parent::__construct(true, 'scope', false);
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        return $context->scope === $target;
    }

    #endregion extends AbstractPseudoSelector
}
