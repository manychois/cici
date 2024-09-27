<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors;

/**
 * Represents an attribute matcher.
 */
enum AttrMatcher: string
{
    case Exists = '';
    case Exact = '=';
    case Includes = '~=';
    case Prefix = '^=';
    case Suffix = '$=';
    case Substring = '*=';
    case Hyphen = '|=';
}
