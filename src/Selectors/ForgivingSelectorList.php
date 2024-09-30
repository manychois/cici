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
    public function __toString(): string
    {
        return \implode(',', \array_map(static fn ($s) => $s->__toString(), $this->selectors));
    }

    #endregion extends AbstractSelector
}
