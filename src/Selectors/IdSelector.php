<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;

/**
 * Represents a ID selector.
 */
class IdSelector extends AbstractSelector
{
    public readonly string $id;

    /**
     * Creates a new instance of the ID selector.
     *
     * @param string $id The ID value.
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    #region extends AbstractSelector

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'type' => 'id',
        ];
    }

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        $actual = $context->getAttributeValue($target, 'id');

        return $actual === $this->id;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '#' . AbstractToken::escapeIdent($this->id);
    }

    #endregion extends AbstractSelector
}
