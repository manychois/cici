<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Matching\NodeType;
use Manychois\Cici\Selectors\AbstractPseudoSelector;

/**
 * Represents the `:empty` pseudo-class.
 */
class EmptyPseudoClass extends AbstractPseudoSelector
{
    /**
     * Creates a new instance of the EmptyPseudoClass class.
     */
    public function __construct()
    {
        parent::__construct(true, 'empty', false);
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        $isEmpty = true;
        foreach ($context->loopDescendants($target, false) as $node) {
            $nodeType = $context->getNodeType($node);
            if ($nodeType === NodeType::Comment || $nodeType === NodeType::Unsupported) {
                continue;
            }
            $isEmpty = false;

            break;
        }

        return $isEmpty;
    }

    #endregion extends AbstractPseudoSelector
}
