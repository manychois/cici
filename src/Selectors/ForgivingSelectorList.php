<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;

/**
 * Represents a forgiving selector list.
 */
class ForgivingSelectorList extends AbstractSelector
{
    /**
     * @var array<int,AbstractSelector>
     */
    public readonly array $selectors;

    /**
     * Creates a new instance of the ForgivingSelectorList class.
     *
     * @param array<int,AbstractSelector> $selectors The selectors inside the forgiving selector list.
     */
    public function __construct(array $selectors)
    {
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
            'type' => 'forgiving-selector-list',
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        if (\count($this->selectors) === 0) {
            return false;
        }

        foreach ($this->selectors as $selector) {
            try {
                if ($selector->matches($context, $target)) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
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
        return \implode(',', $this->selectors);
    }

    #endregion extends AbstractSelector
}
