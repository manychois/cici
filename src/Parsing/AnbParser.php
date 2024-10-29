<?php

declare(strict_types=1);

namespace Manychois\Cici\Parsing;

use Manychois\Cici\Tokenization\Tokens\DelimToken;
use Manychois\Cici\Tokenization\Tokens\DimensionToken;
use Manychois\Cici\Tokenization\Tokens\IdentToken;
use Manychois\Cici\Tokenization\Tokens\NumberToken;
use Manychois\Cici\Tokenization\TokenStream;

/**
 * Parses the `an+b` notation.
 */
class AnbParser
{
    /**
     * Parses the `an+b` notation, if possible.
     *
     * @param TokenStream $tokenStream The token stream to parse.
     *
     * @return AnbNotation|null The parsed `an+b` notation, or `null` if the notation is not valid.
     */
    public function tryParse(TokenStream $tokenStream): ?AnbNotation
    {
        $rollback = $tokenStream->position;
        $t = $tokenStream->tryConsume();

        if ($t instanceof NumberToken && $t->isInt) {
            return new AnbNotation(0, $t->intVal());
        }

        if ($t instanceof IdentToken) {
            if ($t->value === 'odd') {
                return new AnbNotation(2, 1, 'odd');
            }

            if ($t->value === 'even') {
                return new AnbNotation(2, 0, 'even');
            }

            if ($t->value === '-n') {
                $b = 0;
                $rollback = $tokenStream->position;
                $tokenStream->skipWhitespace();
                $t = $tokenStream->tryConsume();
                if ($t instanceof NumberToken && $t->isInt && $t->hasSign) {
                    $b = $t->intVal();
                } elseif ($t instanceof DelimToken && ($t->value === '+' || $t->value === '-')) {
                    $sign = $t->value === '+' ? 1 : -1;
                    $tokenStream->skipWhitespace();
                    $t = $tokenStream->tryConsume();
                    if ($t instanceof NumberToken && $t->isInt && !$t->hasSign) {
                        $b = $sign * $t->intVal();
                    } else {
                        $tokenStream->position = $rollback;
                    }
                }

                return new AnbNotation(-1, $b);
            }

            if ($t->value === '-n-') {
                $tokenStream->skipWhitespace();
                $t = $tokenStream->tryConsume();
                if ($t instanceof NumberToken && $t->isInt && !$t->hasSign) {
                    return new AnbNotation(-1, -$t->intVal());
                }

                $tokenStream->position = $rollback;

                return null;
            }

            if (\preg_match('/^-n-\d+$/', $t->value) === 1) {
                $b = \intval(\substr($t->value, 3));

                return new AnbNotation(-1, -$b);
            }
        }

        if ($t instanceof DimensionToken && $t->isInt) {
            $a = $t->intVal();

            if ($t->unit === 'n') {
                $b = 0;
                $rollback = $tokenStream->position;
                $tokenStream->skipWhitespace();
                $t = $tokenStream->tryConsume();
                if ($t instanceof NumberToken && $t->isInt && $t->hasSign) {
                    $b = $t->intVal();
                } elseif ($t instanceof DelimToken && ($t->value === '+' || $t->value === '-')) {
                    $sign = $t->value === '+' ? 1 : -1;
                    $tokenStream->skipWhitespace();
                    $t = $tokenStream->tryConsume();
                    if ($t instanceof NumberToken && $t->isInt && !$t->hasSign) {
                        $b = $sign * $t->intVal();
                    } else {
                        $tokenStream->position = $rollback;
                    }
                }

                return new AnbNotation($a, $b);
            }

            if ($t->unit === 'n-') {
                $tokenStream->skipWhitespace();
                $t = $tokenStream->tryConsume();
                if ($t instanceof NumberToken && $t->isInt && !$t->hasSign) {
                    return new AnbNotation($a, -$t->intVal());
                }
            } elseif (\preg_match('/^n-\d+$/', $t->unit) === 1) {
                $b = \intval(\substr($t->unit, 2));

                return new AnbNotation($a, -$b);
            }

            $tokenStream->position = $rollback;

            return null;
        }

        if ($t instanceof DelimToken && $t->value === '+') {
            $t = $tokenStream->tryConsume();
        }

        if ($t instanceof IdentToken) {
            if ($t->value === 'n') {
                $b = 0;
                $rollback = $tokenStream->position;
                $tokenStream->skipWhitespace();
                $t = $tokenStream->tryConsume();
                if ($t instanceof NumberToken && $t->isInt && $t->hasSign) {
                    $b = $t->intVal();
                } elseif ($t instanceof DelimToken && ($t->value === '+' || $t->value === '-')) {
                    $sign = $t->value === '+' ? 1 : -1;
                    $tokenStream->skipWhitespace();
                    $t = $tokenStream->tryConsume();
                    if ($t instanceof NumberToken && $t->isInt && !$t->hasSign) {
                        $b = $sign * $t->intVal();
                    } else {
                        $tokenStream->position = $rollback;
                    }
                }

                return new AnbNotation(1, $b);
            }

            if ($t->value === 'n-') {
                $tokenStream->skipWhitespace();
                $t = $tokenStream->tryConsume();
                if ($t instanceof NumberToken && $t->isInt && !$t->hasSign) {
                    return new AnbNotation(1, -$t->intVal());
                }
            } elseif (\preg_match('/^n-\d+$/', $t->value) === 1) {
                $b = \intval(\substr($t->value, 2));

                return new AnbNotation(1, -$b);
            }
        }

        $tokenStream->position = $rollback;

        return null;
    }
}
