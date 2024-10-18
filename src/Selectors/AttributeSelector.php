<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Parsing\WqName;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;

/**
 * Represents an attribute selector.
 */
class AttributeSelector extends AbstractSelector
{
    public readonly WqName $wqName;
    public readonly AttrMatcher $matcher;
    public readonly string $value;
    public readonly ?bool $isCaseSensitive;

    /**
     * Creates a new instance of the attribute selector.
     *
     * @param WqName      $wqName          The attribute name.
     * @param AttrMatcher $matcher         The attribute matcher.
     * @param string      $value           The attribute value.
     * @param bool|null   $isCaseSensitive Whether the value comparison is case-sensitive.
     */
    public function __construct(WqName $wqName, AttrMatcher $matcher, string $value, ?bool $isCaseSensitive)
    {
        $this->wqName = $wqName;
        $this->matcher = $matcher;
        $this->value = $value;
        $this->isCaseSensitive = $isCaseSensitive;
    }

    #region extends AbstractSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $json = [
            'isCaseSensitive' => $this->isCaseSensitive,
            'matcher' => $this->matcher,
            'type' => 'attribute',
            'value' => $this->value,
            'wqName' => $this->wqName,
        ];
        if ($this->isCaseSensitive === null) {
            unset($json['isCaseSensitive']);
        }
        if ($this->matcher === AttrMatcher::Exists) {
            unset($json['value']);
        }

        return $json;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        $actual = $context->getAttributeValue($target, $this->wqName);
        if ($actual === null) {
            return false;
        }
        if ($this->matcher === AttrMatcher::Exists) {
            return true;
        }

        $isCaseSensitive = $this->isCaseSensitive ?? true;
        $expected = $this->value;
        if (!$isCaseSensitive) {
            $actual = \mb_strtolower($actual);
            $expected = \mb_strtolower($expected);
        }

        if ($this->matcher === AttrMatcher::Exact) {
            return $expected === $actual;
        }

        if ($this->matcher === AttrMatcher::Includes) {
            if ($expected === '' || \preg_match('/\s/', $expected) === 1) {
                return false;
            }
            $tokens = \preg_split('/\s/', $actual, -1, \PREG_SPLIT_NO_EMPTY);
            \assert($tokens !== false);

            return \in_array($expected, $tokens, true);
        }

        if ($this->matcher === AttrMatcher::Hyphen) {
            $regex = \sprintf('/^%s(-|$)/', \preg_quote($expected, '/'));

            return \preg_match($regex, $actual) === 1;
        }


        if ($this->matcher === AttrMatcher::Prefix) {
            return $expected !== '' && \str_starts_with($actual, $expected);
        }

        if ($this->matcher === AttrMatcher::Suffix) {
            return $expected !== '' && \str_ends_with($actual, $expected);
        }

        // @phpstan-ignore function.alreadyNarrowedType, identical.alwaysTrue
        \assert($this->matcher === AttrMatcher::Substring);

        return $expected !== '' && \str_contains($actual, $expected);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        $s = '[' . $this->wqName->__toString();
        if ($this->matcher === AttrMatcher::Exists) {
            return $s . ']';
        }

        $s .= $this->matcher->value;
        $s .= '"' . AbstractToken::escape($this->value, '/["\\n\\\\]/') . '"';
        if ($this->isCaseSensitive !== null) {
            $s .= $this->isCaseSensitive ? ' s' : ' i';
        }
        $s .= ']';

        return $s;
    }

    #endregion extends AbstractSelector
}
