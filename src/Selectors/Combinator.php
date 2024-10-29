<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

/**
 * Represents a combinator.
 */
enum Combinator: string
{
    case Descendant = ' ';
    case Child = '>';
    case NextSibling = '+';
    case SubsequentSibling = '~';
    case Column = '||';
}
