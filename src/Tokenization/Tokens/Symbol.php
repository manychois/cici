<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a token that consists of one or more symbols.
 */
enum Symbol: string
{
    case Cdc = '-->';
    case Cdo = '<!--';
    case Colon = ':';
    case Comma = ',';
    case LeftCurlyBracket = '{';
    case LeftParenthesis = '(';
    case LeftSquareBracket = '[';
    case RightCurlyBracket = '}';
    case RightParenthesis = ')';
    case RightSquareBracket = ']';
    case Semicolon = ';';
}
