<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;

/**
 * Represents the `:any-link` pseudo-class.
 */
class AnyLinkPseudoClass extends AbstractPseudoSelector
{
    /**
     * Creates a new instance of the AnyLinkPseudoClass class.
     */
    public function __construct()
    {
        parent::__construct(true, 'any-link', false);
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        if ($context->isHtmlElement($target, 'a', 'area')) {
            return $context->getAttributeValue($target, 'href') !== null;
        }

        return false;
    }

    #endregion extends AbstractPseudoSelector
}
