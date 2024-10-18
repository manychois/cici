<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;

/**
 * Represents a selector consisting of multiple selectors combined by either "and" or "or" logic.
 */
class LogicalSelector extends AbstractSelector
{
    public readonly bool $isAnd;
    /**
     * The selectors to combine.
     *
     * @var array<int,AbstractSelector>
     */
    public readonly array $selectors;

    /**
     * Creates a new instance of the logical selector.
     *
     * @param bool                        $isAnd     `true` to combine by "and" logic; `false` to combine by "or" logic.
     * @param array<int,AbstractSelector> $selectors The selectors to combine.
     */
    public function __construct(bool $isAnd, array $selectors)
    {
        $this->isAnd = $isAnd;
        $this->selectors = $selectors;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'selectors' => $this->selectors,
            'type' => $this->isAnd ? 'and' : 'or',
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        if ($this->isAnd) {
            foreach ($this->selectors as $selector) {
                if (!$selector->matches($context, $target)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($this->selectors as $selector) {
            if ($selector->matches($context, $target)) {
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
        return \implode($this->isAnd ? '' : ',', $this->selectors);
    }

    #endregion extends AbstractSelector
}
