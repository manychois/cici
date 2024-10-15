<?php

declare(strict_types=1);

namespace Manychois\Cici\Parsing;

use Manychois\Cici\Selectors\AbstractPseudoSelector;
use Manychois\Cici\Selectors\PseudoClasses\AnyLinkPseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\EmptyPseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\HasPseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\InputPseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\IsWherePseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\NotPseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\RootPseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\ScopePseudoClass;
use Manychois\Cici\Selectors\PseudoClasses\UnknownPseudoClassSelector;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;
use Manychois\Cici\Tokenization\Tokens\FunctionToken;
use Manychois\Cici\Tokenization\Tokens\IdentToken;
use Manychois\Cici\Tokenization\Tokens\Symbol;
use Manychois\Cici\Tokenization\Tokens\SymbolToken;
use Manychois\Cici\Tokenization\TokenStream;

/**
 * Parses pseudo-class selectors.
 */
class PseudoSelectorParser
{
    private readonly SelectorParser $main;

    /**
     * Creates a new instance of the PseudoSelectorParser class.
     *
     * @param SelectorParser $main The main parser.
     */
    public function __construct(SelectorParser $main)
    {
        $this->main = $main;
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

            return match ($name) {
                'any-link' => new AnyLinkPseudoClass(),
                'checked',
                'disabled',
                'enabled',
                'indeterminate',
                'optional',
                'read-only',
                'read-write',
                'required' => new InputPseudoClass($name),
                'empty' => new EmptyPseudoClass(),
                'root' => new RootPseudoClass(),
                'scope' => new ScopePseudoClass(),
                default => new UnknownPseudoClassSelector($name, false),
            };
        }

        if ($token instanceof FunctionToken) {
            $name = \strtolower($token->value);
            $anyValue = $this->main->consumeAnyValue($tokenStream, false);
            $closeToken = $tokenStream->tryConsume();

            if ($closeToken instanceof SymbolToken && $closeToken->value === Symbol::RightParenthesis) {
                $argsTokenStream = new TokenStream($anyValue, $tokenStream->errors);
                $argsTokenStream->skipWhitespace();
                $pseudoClass = match ($name) {
                    'has' => $this->parseHasPseudoClass($argsTokenStream),
                    'is', 'where' => $this->parseIsPseudoClass($name, $argsTokenStream),
                    'not' => $this->parseNotPseudoClass($argsTokenStream),
                    default => $this->parseUnknownPseudoClass($argsTokenStream, $name, ...$anyValue),
                };
                $argsTokenStream->skipWhitespace();
                if ($argsTokenStream->hasMore()) {
                    throw $argsTokenStream->recordParseException(
                        \sprintf('Unexpected token inside pseudo-class "%s".', $name)
                    );
                }

                return $pseudoClass;
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
     * Parses the `:has()` pseudo-class.
     *
     * @param TokenStream $tokenStream The token stream of the arguments.
     *
     * @return HasPseudoClass The parsed pseudo-class.
     */
    private function parseHasPseudoClass(TokenStream $tokenStream): HasPseudoClass
    {
        $findHas = static fn (AbstractToken $t) => $t instanceof FunctionToken && \strtolower($t->value) === 'has';
        $nestedHas = $tokenStream->first($findHas);
        if ($nestedHas !== null) {
            throw $tokenStream->recordParseException('The :has() pseudo-class cannot be nested.', $nestedHas->position);
        }
        $inner = fn () => $this->main->tryParseRelativeSelector($tokenStream, false);
        $selector = $this->main->tryParseCommaSeparatedList($tokenStream, $inner);
        if ($selector === null) {
            throw $tokenStream->recordParseException('Invalid argument for the :has() pseudo-class.');
        }

        return new HasPseudoClass($selector);
    }

    /**
     * Parses the `:is()` or `:where()` pseudo-class.
     *
     * @param string      $name        The name of the pseudo-class.
     * @param TokenStream $tokenStream The token stream of the arguments.
     *
     * @return IsWherePseudoClass The parsed pseudo-class.
     */
    private function parseIsPseudoClass(string $name, TokenStream $tokenStream): IsWherePseudoClass
    {
        $selector = $this->main->parseForgivingSelectorList($tokenStream);

        return new IsWherePseudoClass($name, $selector);
    }

    /**
     * Parses the `:not()` pseudo-class.
     *
     * @param TokenStream $tokenStream The token stream of the arguments.
     *
     * @return NotPseudoClass The parsed pseudo-class.
     */
    private function parseNotPseudoClass(TokenStream $tokenStream): NotPseudoClass
    {
        $inner = fn () => $this->main->tryParseComplexSelector($tokenStream, true, true);
        $selector = $this->main->tryParseCommaSeparatedList($tokenStream, $inner);
        if ($selector === null) {
            throw $tokenStream->recordParseException('Invalid argument for the :not() pseudo-class.');
        }

        return new NotPseudoClass($selector);
    }

    /**
     * Parses an unknown pseudo-class selector.
     *
     * @param TokenStream   $tokenStream The token stream of the arguments.
     * @param string        $name        The name of the pseudo-class.
     * @param AbstractToken ...$args     The arguments of the selector function, if any.
     *
     * @return UnknownPseudoClassSelector The parsed pseudo-class.
     */
    private function parseUnknownPseudoClass(
        TokenStream $tokenStream,
        string $name,
        AbstractToken ...$args
    ): UnknownPseudoClassSelector {
        while ($tokenStream->hasMore()) {
            $tokenStream->tryConsume();
        }

        return new UnknownPseudoClassSelector($name, true, ...$args);
    }
}
