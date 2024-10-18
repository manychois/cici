<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

/**
 * Represents a legacy pseudo-element selector.
 */
class LegacyPseudoElementSelector extends PseudoElementSelector
{
    /**
     * Creates a new instance of LegacyPseudoElementSelector.
     *
     * @param string $name The name of the pseudo-element. It must be in lowercase.
     */
    public function __construct(string $name)
    {
        parent::__construct($name, false);
    }

    #region extends PseudoElementSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'type' => 'legacy-pseudo-element',
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return ':' . $this->name;
    }

    #endregion extends PseudoElementSelector
}
