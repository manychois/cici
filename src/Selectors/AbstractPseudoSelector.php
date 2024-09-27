<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Tokenization\Tokens\AbstractToken;

/**
 * Represents a pseudo-class / pseudo-element selector.
 */
abstract class AbstractPseudoSelector extends AbstractSelector
{
    public readonly string $name;
    public readonly bool $isFunctional;
    /**
     * @var array<AbstractToken>
     */
    public readonly array $args;
    private readonly bool $isPseudoClass;

    /**
     * Creates a new instance of AbstractPseudoSelector.
     *
     * @param bool          $isPseudoClass `true`: pseudo-class; `false`: pseudo-element.
     * @param string        $name          The name of the pseudo-class / pseudo-element. It must be in lowercase.
     * @param bool          $isFunctional  Whether the selector is functional.
     * @param AbstractToken ...$args       The arguments of the selector function, if any.
     */
    public function __construct(bool $isPseudoClass, string $name, bool $isFunctional, AbstractToken ...$args)
    {
        $this->isPseudoClass = $isPseudoClass;
        \assert(\strtolower($name) === $name);
        $this->name = $name;
        $this->isFunctional = $isFunctional;
        $this->args = $args;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        if ($this->isFunctional) {
            return [
                'args' => $this->args,
                'name' => $this->name,
                'type' => $this->isPseudoClass ? 'pseudo-class' : 'pseudo-element',
            ];
        }

        return [
            'name' => $this->name,
            'type' => $this->isPseudoClass ? 'pseudo-class' : 'pseudo-element',
        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if ($this->isFunctional) {
            $args = \implode(', ', $this->args);

            return ":{$this->name}({$args})";
        }

        return ":{$this->name}";
    }

    #endregion extends AbstractSelector
}
