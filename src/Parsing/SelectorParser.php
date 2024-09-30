<?php

declare(strict_types=1);

namespace Manychois\Cici\Parsing;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Selectors\AbstractSelector;
use Manychois\Cici\Selectors\AttributeSelector;
use Manychois\Cici\Selectors\AttrMatcher;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\Combinator;
use Manychois\Cici\Selectors\ComplexSelector;
use Manychois\Cici\Selectors\CompoundSelector;
use Manychois\Cici\Selectors\ForgivingSelectorList;
use Manychois\Cici\Selectors\IdSelector;
use Manychois\Cici\Selectors\LegacyPseudoElementSelector;
use Manychois\Cici\Selectors\LogicalSelector;
use Manychois\Cici\Selectors\PseudoElementSelector;
use Manychois\Cici\Selectors\RelativeSelector;
use Manychois\Cici\Selectors\TypeSelector;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;
use Manychois\Cici\Tokenization\Tokens\BadStringToken;
use Manychois\Cici\Tokenization\Tokens\BadUrlToken;
use Manychois\Cici\Tokenization\Tokens\DelimToken;
use Manychois\Cici\Tokenization\Tokens\FunctionToken;
use Manychois\Cici\Tokenization\Tokens\HashToken;
use Manychois\Cici\Tokenization\Tokens\IdentToken;
use Manychois\Cici\Tokenization\Tokens\StringToken;
use Manychois\Cici\Tokenization\Tokens\Symbol;
use Manychois\Cici\Tokenization\Tokens\SymbolToken;
use Manychois\Cici\Tokenization\TokenStream;

/**
 * Parses a selector.
 */
class SelectorParser
{
    /**
     * Consumes a list of tokens which are part of the `<any-value>` production.
     *
     * @param TokenStream $tokenStream The token stream to consume.
     * @param bool        $exclComma   Whether to stop at a comma.
     *
     * @return array<int,\Manychois\Cici\Tokenization\Tokens\AbstractToken> The consumed tokens, if any.
     */
    public function consumeAnyValue(TokenStream $tokenStream, bool $exclComma): array
    {
        $values = [];
        $stop = false;
        $bracketOpen = [
            '(' => 0,
            '[' => 0,
            '{' => 0,
        ];
        while ($tokenStream->hasMore()) {
            $token = $tokenStream->tryConsume();
            \assert($token !== null);
            if ($token instanceof BadStringToken || $token instanceof BadUrlToken) {
                $stop = true;
            } elseif ($token instanceof SymbolToken) {
                if (
                    $token->value === Symbol::LeftParenthesis
                    || $token->value === Symbol::LeftSquareBracket
                    || $token->value === Symbol::LeftCurlyBracket
                ) {
                    $bracketOpen[$token->value->value]++;
                } elseif ($token->value === Symbol::Comma) {
                    if ($exclComma && $bracketOpen['('] === 0 && $bracketOpen['['] === 0 && $bracketOpen['{'] === 0) {
                        $stop = true;
                    }
                } else {
                    $correspondingLeftBracket = match ($token->value) {
                        Symbol::RightParenthesis => '(',
                        Symbol::RightSquareBracket => '[',
                        Symbol::RightCurlyBracket => '{',
                        default => null,
                    };
                    if ($correspondingLeftBracket !== null) {
                        $bracketOpen[$correspondingLeftBracket]--;
                        if ($bracketOpen[$correspondingLeftBracket] < 0) {
                            $stop = true;
                        }
                    }
                }
            } elseif ($token instanceof FunctionToken) {
                $bracketOpen['(']++;
            }

            if ($stop) {
                $tokenStream->position--;

                break;
            }

            $values[] = $token;
        }

        return $values;
    }

    /**
     * Parses an attribute selector.
     * This assumes the open square bracket ([) has already been consumed.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return AttributeSelector The parsed attribute selector.
     */
    public function parseAttributeSelector(TokenStream $tokenStream): AttributeSelector
    {
        $tokenStream->skipWhitespace();
        $wqName = $this->tryParseWqName($tokenStream, false);
        if ($wqName === null) {
            throw $tokenStream->recordParseException('Missing attribute name.');
        }

        $tokenStream->skipWhitespace();
        if (!$tokenStream->hasMore()) {
            throw $tokenStream->recordParseException('Attribute selector is not closed.');
        }

        $token = $tokenStream->tryConsume();
        if ($token instanceof SymbolToken && $token->value === Symbol::RightSquareBracket) {
            return new AttributeSelector($wqName, AttrMatcher::Exists, '', null);
        }

        if (!($token instanceof DelimToken)) {
            if ($token !== null) {
                $tokenStream->position--;
            }

            throw $tokenStream->recordParseException('Invalid attribute matcher.');
        }

        $matcher = match ($token->value) {
            '=' => AttrMatcher::Exact,
            '~' => AttrMatcher::Includes,
            '|' => AttrMatcher::Hyphen,
            '^' => AttrMatcher::Prefix,
            '$' => AttrMatcher::Suffix,
            '*' => AttrMatcher::Substring,
            default => null,
        };
        if ($matcher === null) {
            $tokenStream->position--;

            throw $tokenStream->recordParseException('Invalid attribute matcher.');
        }

        if ($matcher !== AttrMatcher::Exact) {
            $token = $tokenStream->tryConsume();
            if (!($token instanceof DelimToken && $token->value === '=')) {
                if ($token !== null) {
                    $tokenStream->position--;
                }

                throw $tokenStream->recordParseException('Invalid attribute matcher.');
            }
        }

        $tokenStream->skipWhitespace();
        $token = $tokenStream->tryConsume();

        if (!($token instanceof IdentToken || $token instanceof StringToken)) {
            if ($token !== null) {
                $tokenStream->position--;
            }

            throw $tokenStream->recordParseException('Missing attribute value.');
        }

        $value = $token->value;
        $tokenStream->skipWhitespace();
        $token = $tokenStream->tryConsume();
        if ($token instanceof SymbolToken && $token->value === Symbol::RightSquareBracket) {
            return new AttributeSelector($wqName, $matcher, $value, null);
        }

        if ($token instanceof IdentToken) {
            $caseSensitive = match (\strtolower($token->value)) {
                'i' => false,
                's' => true,
                default => null,
            };
            if ($caseSensitive === null) {
                $tokenStream->position--;

                throw $tokenStream->recordParseException('Invalid attribute modifier.');
            }

            $tokenStream->skipWhitespace();
            $token = $tokenStream->tryConsume();
            if ($token instanceof SymbolToken && $token->value === Symbol::RightSquareBracket) {
                return new AttributeSelector($wqName, $matcher, $value, $caseSensitive);
            }
        }

        if ($token !== null) {
            $tokenStream->position--;
        }

        throw $tokenStream->recordParseException('Invalid attribute selector.');
    }

    /**
     * Parses a forgiving selector list.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return ForgivingSelectorList The parsed forgiving selector list.
     */
    public function parseForgivingSelectorList(TokenStream $tokenStream): ForgivingSelectorList
    {
        $errorsToIgnore = new ParseExceptionCollection();
        $segments = [];
        while (true) {
            $anyValue = $this->consumeAnyValue($tokenStream, true);
            $segments[] = new TokenStream($anyValue, $errorsToIgnore);

            $token = $tokenStream->tryConsume();
            if ($token === null) {
                break;
            }

            if ($token instanceof SymbolToken && $token->value === Symbol::Comma) {
                continue;
            }
        }

        $selectors = [];
        foreach ($segments as $segment) {
            try {
                $segment->skipWhitespace();
                $complex = $this->tryParseComplexSelector($segment, true, true);
                if ($complex !== null) {
                    $segment->skipWhitespace();
                    if (!$segment->hasMore()) {
                        $selectors[] = $complex;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return new ForgivingSelectorList($selectors);
    }

    /**
     * Parses a combinator, if possible.
     * Do not skip whitespace before calling this method, as it will be parsed as a descendant combinator.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return Combinator|null The parsed combinator, or null if the token stream does not represent a combinator.
     */
    public function tryParseCombinator(TokenStream $tokenStream): ?Combinator
    {
        $startIndex = $tokenStream->position;
        $hasWs = $tokenStream->skipWhitespace();
        $token = $tokenStream->tryConsume();
        if ($token instanceof DelimToken) {
            $combinator = match ($token->value) {
                '>' => Combinator::Child,
                '+' => Combinator::NextSibling,
                '~' => Combinator::SubsequentSibling,
                '|' => Combinator::Column,
                default => null,
            };
            if ($combinator === Combinator::Column) {
                $token = $tokenStream->tryConsume();
                if ($token instanceof DelimToken && $token->value === '|') {
                    return Combinator::Column;
                }
            } elseif ($combinator !== null) {
                return $combinator;
            }
        }
        if ($token !== null) {
            $tokenStream->position--;
        }
        if ($hasWs) {
            return Combinator::Descendant;
        }

        $tokenStream->position = $startIndex;

        return null;
    }

    /**
     * Parses a comma-separated list of selectors.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     * @param \Closure    $parseInner  The function to parse each inner selector.
     *
     * @return AbstractSelector|null The parsed selector list, or null if the token stream does not represent a
     * selector list.
     */
    public function tryParseCommaSeparatedList(TokenStream $tokenStream, \Closure $parseInner): ?AbstractSelector
    {
        $restoreIndex = $tokenStream->position;
        $selectors = [];
        while (true) {
            $tokenStream->skipWhitespace();
            $selector = $parseInner();
            if ($selector === null) {
                break;
            }
            \assert($selector instanceof AbstractSelector);
            $selectors[] = $selector;
            $restoreIndex = $tokenStream->position;
            $tokenStream->skipWhitespace();
            $token = $tokenStream->tryConsume();
            if (!($token instanceof SymbolToken && $token->value === Symbol::Comma)) {
                break;
            }
        }

        $tokenStream->position = $restoreIndex;
        if (\count($selectors) === 0) {
            return null;
        }

        if (\count($selectors) === 1) {
            return $selectors[0];
        }

        return new LogicalSelector(false, $selectors);
    }

    /**
     * Parses a complex selector, if possible.
     *
     * @param TokenStream $tokenStream            The token stream to parse.
     * @param bool        $real                   Whether to parse a real complex selector.
     * @param bool        $ignoreDefaultNamespace Whether to ignore the default namespace.
     *
     * @return AbstractSelector|null The parsed complex selector, or null if the token stream does not represent a
     * complex selector.
     */
    public function tryParseComplexSelector(
        TokenStream $tokenStream,
        bool $real,
        bool $ignoreDefaultNamespace
    ): ?AbstractSelector {
        $selectors = [];
        $combinators = [];

        $unit = $real
            ? $this->tryParseCompoundSelector($tokenStream, $ignoreDefaultNamespace)
            : $this->tryParseComplexSelectorUnit($tokenStream, $ignoreDefaultNamespace);
        if ($unit === null) {
            return null;
        }
        $selectors[] = $unit;
        $combinators = [];
        while (true) {
            $index = $tokenStream->position;
            $combinator = $this->tryParseCombinator($tokenStream);
            if ($combinator === null) {
                break;
            }
            $tokenStream->skipWhitespace();
            $unit = $real
                ? $this->tryParseCompoundSelector($tokenStream, $ignoreDefaultNamespace)
                : $this->tryParseComplexSelectorUnit($tokenStream, $ignoreDefaultNamespace);
            if ($unit === null) {
                $tokenStream->position = $index;

                break;
            }
            $combinators[] = $combinator;
            $selectors[] = $unit;
        }

        if (\count($selectors) === 1) {
            return $selectors[0];
        }

        return new ComplexSelector($selectors, $combinators);
    }

    /**
     * Parses a complex selector unit, if possible.
     *
     * @param TokenStream $tokenStream            The token stream to parse.
     * @param bool        $ignoreDefaultNamespace Whether to ignore the default namespace.
     *
     * @return AbstractSelector|null The parsed complex selector unit, or null if the token stream does not represent a
     * complex selector unit.
     */
    public function tryParseComplexSelectorUnit(
        TokenStream $tokenStream,
        bool $ignoreDefaultNamespace
    ): ?AbstractSelector {
        $selectors = [];
        $compound = $this->tryParseCompoundSelector($tokenStream, $ignoreDefaultNamespace);
        if ($compound !== null) {
            $selectors[] = $compound;
        }

        while (true) {
            $pseudo = $this->tryParsePseudoCompoundSelector($tokenStream);
            if ($pseudo === null) {
                break;
            }
            if ($pseudo instanceof LogicalSelector && $pseudo->isAnd) {
                $selectors = \array_merge($selectors, $pseudo->selectors);
            } else {
                $selectors[] = $pseudo;
            }
        }

        if (\count($selectors) === 0) {
            return null;
        }
        if (\count($selectors) === 1) {
            return $selectors[0];
        }

        return new LogicalSelector(true, $selectors);
    }

    /**
     * Parses a compound selector, if possible.
     *
     * @param TokenStream $tokenStream            The token stream to parse.
     * @param bool        $ignoreDefaultNamespace Whether to ignore the default namespace.
     *
     * @return CompoundSelector|null The parsed compound selector, or null if the token stream does not represent a
     * compound selector.
     */
    public function tryParseCompoundSelector(TokenStream $tokenStream, bool $ignoreDefaultNamespace): ?CompoundSelector
    {
        $subclasses = [];
        $type = $this->tryParseTypeSelector($tokenStream);
        while (true) {
            $subclass = $this->tryParseSubclassSelector($tokenStream);
            if ($subclass === null) {
                break;
            }
            $subclasses[] = $subclass;
        }

        if ($type === null && \count($subclasses) === 0) {
            return null;
        }

        return new CompoundSelector($type, $subclasses, $ignoreDefaultNamespace);
    }

    /**
     * Parses a compound selector, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return AbstractSelector|null The parsed compound selector, or null if the token stream does not represent a
     * compound selector.
     */
    public function tryParsePseudoCompoundSelector(TokenStream $tokenStream): ?AbstractSelector
    {
        $token = $tokenStream->tryConsume();
        if (!($token instanceof SymbolToken && $token->value === Symbol::Colon)) {
            if ($token !== null) {
                $tokenStream->position--;
            }

            return null;
        }

        $pseudoElement = $this->tryParsePseudoElementSelector($tokenStream);
        if ($pseudoElement === null) {
            $tokenStream->position--;

            return null;
        }

        /**
         * @var array<int,AbstractSelector> $selectors
         */
        $selectors = [$pseudoElement];

        $psp = new PseudoSelectorParser($this);
        while (true) {
            $token = $tokenStream->tryConsume();
            if (!($token instanceof SymbolToken && $token->value === Symbol::Colon)) {
                if ($token !== null) {
                    $tokenStream->position--;
                }

                break;
            }

            $pseudoClass = $psp->tryParsePseudoClassSelector($tokenStream);
            if ($pseudoClass === null) {
                $tokenStream->position--;

                break;
            }
            $selectors[] = $pseudoClass;
        }

        if (\count($selectors) === 1) {
            return $selectors[0];
        }

        return new LogicalSelector(true, $selectors);
    }

    /**
     * Parses a pseudo-element selector, if possible.
     * This assumes the colon (:) has already been consumed.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return PseudoElementSelector|null The parsed pseudo-element selector, or null if the selector is not a
     * pseudo-element selector.
     */
    public function tryParsePseudoElementSelector(TokenStream $tokenStream): ?PseudoElementSelector
    {
        $startIndex = $tokenStream->position;
        $token = $tokenStream->tryConsume();

        if ($token instanceof IdentToken) {
            $name = \strtolower($token->value);
            if (\in_array($name, ['before', 'after', 'first-line', 'first-letter'], true)) {
                return new LegacyPseudoElementSelector($name);
            }
        }

        if ($token instanceof SymbolToken && $token->value === Symbol::Colon) {
            $psp = new PseudoSelectorParser($this);
            $pseudoClass = $psp->tryParsePseudoClassSelector($tokenStream);
            if ($pseudoClass !== null) {
                return new PseudoElementSelector($pseudoClass->name, $pseudoClass->isFunctional, ...$pseudoClass->args);
            }
        }

        $tokenStream->position = $startIndex;

        return null;
    }

    /**
     * Parses a relative selector, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     * @param bool        $real        Whether to parse a real relative selector.
     *
     * @return RelativeSelector|null The parsed relative selector, or null if the token stream does not represent a
     * relative selector.
     */
    public function tryParseRelativeSelector(TokenStream $tokenStream, bool $real): ?RelativeSelector
    {
        $startIndex = $tokenStream->position;

        $combinator = $this->tryParseCombinator($tokenStream);
        if ($combinator === null) {
            $combinator = Combinator::Descendant;
        }

        $tokenStream->skipWhitespace();
        $complex = $this->tryParseComplexSelector($tokenStream, $real, false);
        if ($complex === null) {
            $tokenStream->position = $startIndex;

            return null;
        }

        return new RelativeSelector($combinator, $complex);
    }

    /**
     * Parses a subclass selector, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return AbstractSelector|null The parsed subclass selector, or null if the token stream does not represent a
     * subclass selector.
     */
    public function tryParseSubclassSelector(TokenStream $tokenStream): ?AbstractSelector
    {
        $token = $tokenStream->tryConsume();
        if ($token instanceof HashToken) {
            if ($token->isIdType) {
                return new IdSelector($token->value);
            }
            $tokenStream->position--;

            throw $tokenStream->recordParseException('Invalid ID selector.');
        }

        if ($token instanceof DelimToken && $token->value === '.') {
            $token = $tokenStream->tryConsume();
            if ($token instanceof IdentToken) {
                return new ClassSelector($token->value);
            }

            if ($token !== null) {
                $tokenStream->position--;
            }

            throw $tokenStream->recordParseException('Invalid class selector.');
        }

        if ($token instanceof SymbolToken) {
            if ($token->value === Symbol::LeftSquareBracket) {
                return $this->parseAttributeSelector($tokenStream);
            }

            if ($token->value === Symbol::Colon) {
                $psp = new PseudoSelectorParser($this);
                $pseudoClass = $psp->tryParsePseudoClassSelector($tokenStream);
                if ($pseudoClass === null) {
                    $tokenStream->position--;

                    return null;
                }

                return $pseudoClass;
            }
        }

        if ($token !== null) {
            $tokenStream->position--;
        }

        return null;
    }

    /**
     * Parses a type selector, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return TypeSelector|null The parsed type selector, or null if the token stream does not represent a type
     * selector.
     */
    public function tryParseTypeSelector(TokenStream $tokenStream): ?TypeSelector
    {
        $wqName = $this->tryParseWqName($tokenStream, true);
        if ($wqName === null) {
            return null;
        }

        return new TypeSelector($wqName);
    }

    /**
     * Parses a wildcard qualified name, if possible.
     *
     * @param TokenStream $tokenStream            The token stream to parse.
     * @param bool        $allowWildcastLocalName Whether to allow the local name to be a wildcard.
     *
     * @return WqName|null The parsed wildcard qualified name, or null if the token stream does not represent a wildcard
     * qualified name.
     */
    public function tryParseWqName(TokenStream $tokenStream, bool $allowWildcastLocalName): ?WqName
    {
        $extractName = static function (?AbstractToken $token): ?string {
            if ($token instanceof IdentToken) {
                return $token->value;
            }
            if ($token instanceof DelimToken && $token->value === '*') {
                return '*';
            }

            return null;
        };
        $isPipe = static fn ($token) => $token instanceof DelimToken && $token->value === '|';

        $startIndex = $tokenStream->position;
        $token = $tokenStream->tryConsume();
        if ($token === null) {
            return null;
        }

        $prefix = $extractName($token);
        $hasPipe = false;
        $localName = null;
        if ($prefix === null) {
            if ($isPipe($token)) {
                $hasPipe = true;
                $token = $tokenStream->tryConsume();
                $localName = $extractName($token);
            }
        }
        if (!$hasPipe) {
            $token = $tokenStream->tryConsume();
            if ($isPipe($token)) {
                $hasPipe = true;
                $token = $tokenStream->tryConsume();
                $localName = $extractName($token);
            }
        }

        if ($localName === '*' && !$allowWildcastLocalName || $localName === null) {
            $localName = $prefix;
            $prefix = null;
            $tokenStream->position = $startIndex + 1;
        }
        if ($localName === '*' && !$allowWildcastLocalName || $localName === null) {
            $tokenStream->position = $startIndex;

            return null;
        }

        return new WqName($hasPipe, $prefix, $localName);
    }
}
