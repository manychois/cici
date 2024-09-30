<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;

/**
 * Represents an unknown pseudo-class selector.
 */
class UnknownPseudoClassSelector extends AbstractPseudoSelector
{
    /**
     * Creates a new instance of UnknownPseudoClassSelector.
     *
     * @param string        $name         The name of the unknown pseudo-class. It must be in lowercase.
     * @param bool          $isFunctional Whether the selector is functional.
     * @param AbstractToken ...$args      The arguments of the selector function, if any.
     */
    public function __construct(string $name, bool $isFunctional, AbstractToken ...$args)
    {
        parent::__construct(true, $name, $isFunctional, ...$args);
    }


    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        throw new \RuntimeException(\sprintf('Matching pseudo-class %s is not supported.', $this->name));
    }

    #endregion extends AbstractPseudoSelector
}
