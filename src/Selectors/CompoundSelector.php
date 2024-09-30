<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;

/**
 * Represents a compound selector.
 */
class CompoundSelector extends LogicalSelector
{
    private readonly bool $ignoreDefaultNamespace;

    /**
     * Initializes a new instance of the CompoundSelector class.
     *
     * @param TypeSelector|null           $typeSelector           The type selector.
     * @param array<int,AbstractSelector> $subclasses             The subclass selectors.
     * @param bool                        $ignoreDefaultNamespace Whether to ignore the default namespace.
     */
    public function __construct(?TypeSelector $typeSelector, array $subclasses, bool $ignoreDefaultNamespace)
    {
        $selectors = [];
        if ($typeSelector !== null) {
            $selectors[] = $typeSelector;
        }
        foreach ($subclasses as $subclass) {
            $selectors[] = $subclass;
        }

        parent::__construct(true, $selectors);

        $this->ignoreDefaultNamespace = $ignoreDefaultNamespace;
    }

    #region extends LogicalSelector

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'selectors' => $this->selectors,
            'type' => 'compound',
        ];
    }

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        if (!$this->ignoreDefaultNamespace && !($this->selectors[0] instanceof TypeSelector)) {
            if (!$context->matchDefaultNamespace($target)) {
                return false;
            }
        }

        return parent::matches($context, $target);
    }

    #endregion extends LogicalSelector
}
