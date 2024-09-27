<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;

/**
 * Represents a class selector.
 */
class ClassSelector extends AbstractSelector
{
    public readonly string $className;
    private readonly bool $containsWhitespace;

    /**
     * Creates a new instance of the class selector.
     *
     * @param string $className The class name.
     */
    public function __construct(string $className)
    {
        $this->className = $className;
        $this->containsWhitespace = \preg_match('/\s/', $className) === 1;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'className' => $this->className,
            'type' => 'class',
        ];
    }

    /**
     * @inheritDoc
     */
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        if ($this->containsWhitespace) {
            return false;
        }

        $actual = $context->getAttributeValue($target, 'class');
        if ($actual === null) {
            return false;
        }

        $tokens = \preg_split('/\s/', $actual, -1, \PREG_SPLIT_NO_EMPTY);
        \assert($tokens !== false);

        return \in_array($this->className, $tokens, true);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return '.' . AbstractToken::escapeIdent($this->className);
    }

    #endregion extends AbstractSelector
}
