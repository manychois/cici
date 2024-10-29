<?php

declare(strict_types=1);

namespace Manychois\Cici\Matching;

/**
 * Represents the type of a node.
 */
enum NodeType: int
{
    /**
     * Attributes, processing instructions, CDATA sections, etc.
     */
    case Unsupported = 0;
    case Element = 1;
    case Text = 3;
    case Comment = 8;
    case Document = 9;
    case DocumentType = 10;
    case DocumentFragment = 11;
}
