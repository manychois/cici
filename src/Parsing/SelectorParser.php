<?php

declare(strict_types=1);

namespace Manychois\Cici\Parsing;

use Manychois\Cici\Selectors\AbstractPseudoSelector;
use Manychois\Cici\Selectors\AbstractSelector;
use Manychois\Cici\Selectors\AttributeSelector;
use Manychois\Cici\Selectors\AttrMatcher;
use Manychois\Cici\Selectors\ClassSelector;
use Manychois\Cici\Selectors\IdSelector;
use Manychois\Cici\Selectors\LegacyPseudoElementSelector;
use Manychois\Cici\Selectors\LogicalSelector;
use Manychois\Cici\Selectors\PseudoElementSelector;
use Manychois\Cici\Selectors\TypeSelector;
use Manychois\Cici\Selectors\UnknownPseudoClassSelector;
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
     *
     * @return array<int,\Manychois\Cici\Tokenization\Tokens\AbstractToken> The consumed tokens, if any.
     */
    public function consumeAnyValue(TokenStream $tokenStream): array
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
     * Parses a compound selector, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return LogicalSelector|null The parsed compound selector, or null if the token stream does not represent a
     * compound selector.
     */
    public function tryParseCompoundSelector(TokenStream $tokenStream): ?LogicalSelector
    {
        $selectors = [];
        $type = $this->tryParseTypeSelector($tokenStream);
        if ($type !== null) {
            $selectors[] = $type;
        }
        while (true) {
            $subclass = $this->tryParseSubclassSelector($tokenStream);
            if ($subclass === null) {
                break;
            }
            $selectors[] = $subclass;
        }

        if (\count($selectors) === 0) {
            return null;
        }

        return new LogicalSelector(true, $selectors);
    }

    /**
     * Parses a pseudo-class selector, if possible.
     * This assumes the colon (:) has already been consumed.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return AbstractPseudoSelector|null The parsed pseudo-class selector, or null if the selector is not a
     * pseudo-class selector.
     */
    public function tryParsePseudoClassSelector(TokenStream $tokenStream): ?AbstractPseudoSelector
    {
        $startIndex = $tokenStream->position;
        $token = $tokenStream->tryConsume();

        if ($token instanceof IdentToken) {
            $name = \strtolower($token->value);

            return new UnknownPseudoClassSelector($name, false);
        }

        if ($token instanceof FunctionToken) {
            $name = \strtolower($token->value);
            $anyValue = $this->consumeAnyValue($tokenStream);
            $closeToken = $tokenStream->tryConsume();

            if ($closeToken instanceof SymbolToken && $closeToken->value === Symbol::RightParenthesis) {
                return new UnknownPseudoClassSelector($name, true, ...$anyValue);
            }

            if ($closeToken !== null) {
                $tokenStream->position--;
            }

            throw $tokenStream->recordParseException('The function is not closed.');
        }

        $tokenStream->position = $startIndex;

        return null;
    }

    /**
     * Parses a compound selector, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return LogicalSelector|null The parsed compound selector, or null if the token stream does not represent a
     * compound selector.
     */
    public function tryParsePseudoCompoundSelector(TokenStream $tokenStream): ?LogicalSelector
    {
        $pseudoElement = $this->tryParsePseudoElementSelector($tokenStream);
        if ($pseudoElement === null) {
            return null;
        }

        /**
         * @var array<int,AbstractSelector> $selectors
         */
        $selectors = [$pseudoElement];

        while (true) {
            $pseudoClass = $this->tryParsePseudoClassSelector($tokenStream);
            if ($pseudoClass === null) {
                break;
            }
            $selectors[] = $pseudoClass;
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
            $pseudoClass = $this->tryParsePseudoClassSelector($tokenStream);
            if ($pseudoClass !== null) {
                return new PseudoElementSelector($pseudoClass->name, $pseudoClass->isFunctional, ...$pseudoClass->args);
            }
        }

        $tokenStream->position = $startIndex;

        return null;
    }

    /**
     * Parses a simple selector, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return AbstractSelector|null The parsed simple selector, or null if the token stream does not represent a simple
     * selector.
     */
    public function tryParseSimpleSelector(TokenStream $tokenStream): ?AbstractSelector
    {
        return $this->tryParseTypeSelector($tokenStream) ?? $this->tryParseSubclassSelector($tokenStream);
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

            throw $tokenStream->recordParseException('Invalid class name.');
        }

        if ($token instanceof SymbolToken) {
            if ($token->value === Symbol::LeftSquareBracket) {
                return $this->parseAttributeSelector($tokenStream);
            }

            if ($token->value === Symbol::Colon) {
                $pseudoClass = $this->tryParsePseudoClassSelector($tokenStream);
                if ($pseudoClass === null) {
                    throw $tokenStream->recordParseException('Invalid pseudo-class or pseudo-element selector.');
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
