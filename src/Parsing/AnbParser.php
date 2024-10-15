<?php

declare(strict_types=1);

namespace Manychois\Cici\Parsing;

use Manychois\Cici\Matching\AnbNotation;
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
        $originalIndex = $tokenStream->position;
        $a = 0;
        $b = 0;
        $keyword = '';
        $t1 = $tokenStream->tryConsume();
        if ($t1 instanceof NumberToken) {
            if (!$t1->isInt) {
                $tokenStream->position = $originalIndex;

                return null;
            }

            $a = 0;
            $b = $t1->intVal();
        } elseif ($t1 instanceof IdentToken) {
            $ident = \strtolower($t1->value);
            if ($ident === 'odd' || $ident === 'even') {
                $a = 2;
                $b = $ident === 'odd' ? 1 : 0;
                $keyword = $ident;
            } elseif ($ident === '-n') {
                $a = -1;
                $tokenStream->skipWhitespace();
                $t2 = $tokenStream->tryConsume();
                if ($t2 instanceof NumberToken && $t2->isInt && $t2->hasSign) {
                    $b = $t2->intVal();
                } elseif ($t2 instanceof DelimToken && ($t2->value === '+' || $t2->value === '-')) {
                    $tokenStream->skipWhitespace();
                    $t3 = $tokenStream->tryConsume();
                    if ($t3 instanceof NumberToken && $t3->isInt && !$t3->hasSign) {
                        $b = $t3->intVal() * ($t2->value === '+' ? 1 : -1);
                    } else {
                        $tokenStream->position = $originalIndex + 1;
                    }
                } else {
                    $tokenStream->position = $originalIndex + 1;
                }
            } elseif ($ident === '-n-') {
                $a = -1;
                $tokenStream->skipWhitespace();
                $t2 = $tokenStream->tryConsume();
                if (!($t2 instanceof NumberToken) || !$t2->isInt || $t2->hasSign) {
                    $tokenStream->position = $originalIndex;

                    return null;
                }

                $b = -$t2->intVal();
            } elseif (\preg_match('/^-n-\d+$/', $ident) === 1) {
                $a = -1;
                $b = -\intval(\substr($ident, 3));
            } else {
                $tokenStream->position = $originalIndex;

                return null;
            }
        } elseif ($t1 instanceof DimensionToken) {
            $unit = \strtolower($t1->unit);
            if ($unit === 'n') {
                $a = $t1->intVal();
                $tokenStream->skipWhitespace();
                $t2 = $tokenStream->tryConsume();
                if ($t2 instanceof NumberToken && $t2->isInt && $t2->hasSign) {
                    $b = $t2->intVal();
                } elseif ($t2 instanceof DelimToken && ($t2->value === '+' || $t2->value === '-')) {
                    $tokenStream->skipWhitespace();
                    $t3 = $tokenStream->tryConsume();
                    if ($t3 instanceof NumberToken && $t3->isInt && !$t3->hasSign) {
                        $b = $t3->intVal() * ($t2->value === '+' ? 1 : -1);
                    } else {
                        $tokenStream->position = $originalIndex + 1;
                    }
                } else {
                    $tokenStream->position = $originalIndex + 1;
                }
            } elseif ($unit === 'n-') {
                $a = $t1->intVal();
                $tokenStream->skipWhitespace();
                $t2 = $tokenStream->tryConsume();
                if (!($t2 instanceof NumberToken) || !$t2->isInt || $t2->hasSign) {
                    $tokenStream->position = $originalIndex;

                    return null;
                }

                $b = -$t2->intVal();
            } elseif (\preg_match('/^n-\d+$/', $unit) === 1) {
                $a = $t1->intVal();
                $b = -\intval(\substr($unit, 2));
            } else {
                $tokenStream->position = $originalIndex;

                return null;
            }
        } elseif ($t1 instanceof DelimToken && $t1->value === '+') {
            $a = 1;
            $t2 = $tokenStream->tryConsume();
            if (!($t2 instanceof IdentToken)) {
                $tokenStream->position = $originalIndex;

                return null;
            }
            $ident = \strtolower($t2->value);
            if ($ident === 'n') {
                $tokenStream->skipWhitespace();
                $t3 = $tokenStream->tryConsume();
                if ($t3 instanceof NumberToken && $t3->isInt && $t3->hasSign) {
                    $b = $t3->intVal();
                } elseif ($t3 instanceof DelimToken && ($t3->value === '+' || $t3->value === '-')) {
                    $tokenStream->skipWhitespace();
                    $t4 = $tokenStream->tryConsume();
                    if ($t4 instanceof NumberToken && $t4->isInt && !$t4->hasSign) {
                        $b = $t4->intVal() * ($t3->value === '+' ? 1 : -1);
                    } else {
                        $tokenStream->position = $originalIndex + 2;
                    }
                } else {
                    $tokenStream->position = $originalIndex + 2;
                }
            } elseif ($ident === 'n-') {
                $tokenStream->skipWhitespace();
                $t3 = $tokenStream->tryConsume();
                if (!($t3 instanceof NumberToken) || !$t3->isInt || $t3->hasSign) {
                    $tokenStream->position = $originalIndex;

                    return null;
                }

                $b = -$t3->intVal();
            } elseif (\preg_match('/^n-\d+$/', $ident) === 1) {
                $b = -\intval(\substr($ident, 2));
            } else {
                $tokenStream->position = $originalIndex;

                return null;
            }
        } else {
            $tokenStream->position = $originalIndex;

            return null;
        }

        return new AnbNotation($a, $b, $keyword);
    }
}
