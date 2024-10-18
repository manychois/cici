<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;

/**
 * Represents a complex selector.
 */
class ComplexSelector extends AbstractSelector
{
    /**
     * @var array<int,Combinator>
     */
    public readonly array $combinators;
    /**
     * @var array<int,AbstractSelector>
     */
    public readonly array $selectors;

    /**
     * Creates a new instance of ComplexSelector.
     *
     * @param array<int,AbstractSelector> $selectors   The selectors.
     * @param array<int,Combinator>       $combinators The combinators.
     */
    public function __construct(array $selectors, array $combinators)
    {
        \assert(\count($selectors) > 1, 'The number of selectors must be greater than 1.');
        \assert(
            \count($selectors) === \count($combinators) + 1,
            'The number of selectors must be one more than the number of combinators.'
        );
        $this->selectors = $selectors;
        $this->combinators = $combinators;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'combinators' => $this->combinators,
            'selectors' => $this->selectors,
            'type' => 'complex',
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        $count = \count($this->selectors);
        $lastSelector = $this->selectors[$count - 1];
        if (!$lastSelector->matches($context, $target)) {
            return false;
        }

        $count--;
        $lastCombinator = $this->combinators[$count - 1];
        if ($count === 1) {
            $reduced = $this->selectors[0];
        } else {
            $reduced = new self(
                \array_slice($this->selectors, 0, $count),
                \array_slice($this->combinators, 0, $count - 1)
            );
        }

        foreach ($context->loopLeftCandidates($target, $lastCombinator) as $candidate) {
            if ($reduced->matches($context, $candidate)) {
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
        $count = \count($this->selectors);
        $result = $this->selectors[0]->__toString();
        for ($i = 1; $i < $count; $i++) {
            $result .= $this->combinators[$i - 1]->value . $this->selectors[$i]->__toString();
        }

        return $result;
    }

    #endregion extends AbstractSelector
}
