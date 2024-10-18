<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Parsing\AnbNotation;
use Manychois\Cici\Selectors\AbstractPseudoSelector;
use Manychois\Cici\Selectors\AbstractSelector;

/**
 * Represents a child-indexed pseudo-class selector, i.e. `:nth-child()`, `:nth-last-child()`, `:first-child`,
 * `:last-child` and `:only-child`.
 */
class ChildIndexedPseudoClass extends AbstractPseudoSelector
{
    public readonly ?AnbNotation $anb;
    public readonly ?AbstractSelector $of;

    /**
     * Creates a new instance of the ChildIndexedPseudoClass class.
     *
     * @param string                $name The name of the pseudo-class.
     * @param AnbNotation|null      $anb  The `an+b` notation.
     * @param AbstractSelector|null $of   The selector to match the children against.
     */
    public function __construct(string $name, ?AnbNotation $anb, ?AbstractSelector $of = null)
    {
        parent::__construct(true, $name, $anb !== null);

        $this->anb = $anb;
        $this->of = $of;
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        $parent = $context->getParentNode($target);
        if ($parent === null) {
            return false;
        }

        $siblings = \iterator_to_array($context->loopChildren($parent));

        if ($this->name === 'first-child') {
            return $target === ($siblings[0] ?? null);
        }
        if ($this->name === 'last-child') {
            return $target === ($siblings[\count($siblings) - 1] ?? null);
        }
        if ($this->name === 'only-child') {
            return \count($siblings) === 1 && $target === $siblings[0];
        }

        if ($this->of !== null) {
            $siblings = \array_filter($siblings, fn ($ele): bool => $this->of->matches($context, $ele));
            $siblings = \array_values($siblings);
        }
        $index = \array_search($target, $siblings, true);
        if (!\is_int($index)) {
            return false;
        }
        $count = \count($siblings);
        $index = match ($this->name) {
            'nth-child' => $index + 1,
            'nth-last-child' => $count - $index,
            default => 0,
        };

        \assert($this->anb !== null);

        return $this->anb->matches($index);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        /** @var array<string,mixed> $json */
        $json = parent::jsonSerialize();
        if (!$this->isFunctional) {
            return $json;
        }

        \assert($this->anb !== null);
        $json['formula'] = $this->anb;
        if ($this->of !== null) {
            $json['of'] = $this->of;
        }
        unset($json['args']);
        \ksort($json);

        return $json;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        if (!$this->isFunctional) {
            return parent::__toString();
        }
        \assert($this->anb !== null);
        $args = $this->anb->__toString();
        if ($this->of !== null) {
            $args .= ' of ' . $this->of->__toString();
        }

        return \sprintf(':%s(%s)', $this->name, $args);
    }

    #endregion extends AbstractPseudoSelector
}
