<?php

declare(strict_types=1);

namespace Manychois\Cici\Selectors\PseudoClasses;

use Manychois\Cici\Matching\AbstractMatchContext;
use Manychois\Cici\Selectors\AbstractPseudoSelector;

/**
 * Represents pseudo-classes related to input elements.
 */
class InputPseudoClass extends AbstractPseudoSelector
{
    /**
     * Creates a new instance of the InputPseudoClass class.
     *
     * @param string $name The name of the pseudo-class.
     */
    public function __construct(string $name)
    {
        parent::__construct(true, $name, false);
    }

    #region extends AbstractPseudoSelector

    /**
     * @inheritDoc
     */
    #[\Override]
    public function matches(AbstractMatchContext $context, object $target): bool
    {
        if ($this->name === 'disabled') {
            return $context->isActuallyDisabled($target);
        }
        if ($this->name === 'enabled') {
            return $context->isHtmlElement(
                $target,
                'button',
                'input',
                'select',
                'textarea',
                'optgroup',
                'option',
                'fieldset'
            ) && !$context->isActuallyDisabled($target);
        }
        if ($this->name === 'required') {
            return $context->isHtmlElement(
                $target,
                'input',
                'select',
                'textarea'
            ) && $context->getAttributeValue($target, 'required') !== null;
        }
        if ($this->name === 'optional') {
            return $context->isHtmlElement(
                $target,
                'input',
                'select',
                'textarea'
            ) && $context->getAttributeValue($target, 'required') === null;
        }
        if ($this->name === 'read-write') {
            return $context->isReadWritable($target);
        }
        if ($this->name === 'read-only') {
            return !$context->isReadWritable($target);
        }
        if ($this->name === 'checked') {
            if ($context->isHtmlElement($target, 'input')) {
                \assert($target instanceof \DOMElement);
                $type = $context->getAttributeValue($target, 'type');
                if ($type === 'checkbox' || $type === 'radio') {
                    return $context->getAttributeValue($target, 'checked') !== null;
                }
            } elseif ($context->isHtmlElement($target, 'option')) {
                return $context->getAttributeValue($target, 'selected') !== null;
            }
        }
        if ($this->name === 'indeterminate') {
            if ($context->isHtmlElement($target, 'input')) {
                \assert($target instanceof \DOMElement);
                $type = $context->getAttributeValue($target, 'type');
                if ($type === 'radio') {
                    $group = $context->getRadioButtonGroup($target);
                    $hasChecked = false;
                    foreach ($group as $radio) {
                        if ($context->getAttributeValue($radio, 'checked') !== null) {
                            $hasChecked = true;

                            break;
                        }
                    }

                    return !$hasChecked;
                }
            } elseif ($context->isHtmlElement($target, 'progress')) {
                return $context->getAttributeValue($target, 'value') === null;
            }

            return false;
        }

        return false;
    }

    #endregion extends AbstractPseudoSelector
}
