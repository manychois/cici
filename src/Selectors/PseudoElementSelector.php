<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;

/**
 * Represents a pseudo-element selector.
 */
class PseudoElementSelector extends AbstractPseudoSelector
{
    /**
     * Creates a new instance of PseudoElementSelector.
     *
     * @param string        $name         The name of the pseudo-element. It must be in lowercase.
     * @param bool          $isFunctional Whether the pseudo-element is functional.
     * @param AbstractToken ...$args      The arguments of the pseudo-element function, if any.
     */
    public function __construct(string $name, bool $isFunctional, AbstractToken ...$args)
    {
        parent::__construct(false, $name, $isFunctional, ...$args);
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        throw new \RuntimeException('Matching pseudo-elements is not supported.');
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return "::{$this->name}";
    }

    #endregion extends AbstractPseudoSelector
}
