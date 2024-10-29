<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Parsing\WqName;

/**
 * Represents a type selector.
 */
class TypeSelector extends AbstractSelector
{
    public readonly WqName $wqName;

    /**
     * Creates a new instance of the type selector.
     *
     * @param WqName $wqName The qualified name of the type.
     */
    public function __construct(WqName $wqName)
    {
        $this->wqName = $wqName;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return [
            'type' => 'type',
            'wqName' => $this->wqName,
        ];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        return $context->matchElementType($target, $this->wqName);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->wqName->__toString();
    }


    #endregion extends AbstractSelector
}
