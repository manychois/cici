<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;

/**
 * Represents the `:root` pseudo-class.
 */
class RootPseudoClass extends AbstractPseudoSelector
{
    /**
     * Creates a new instance of the RootPseudoClass class.
     */
    public function __construct()
    {
        parent::__construct(true, 'root', false);
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        return $context->root === $target;
    }

    #endregion extends AbstractPseudoSelector
}
