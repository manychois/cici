<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Selectors\PseudoClasses;

use Manychois\Cici\Matching\DomNodeMatchContext;
use Manychois\Cici\Selectors\PseudoClasses\InputPseudoClass;
use PHPUnit\Framework\TestCase;

class InputPseudoClassTest extends TestCase
{
    public function testMatchesDisabled(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'text');
        $body->appendChild($input);
        $a = $doc->createElement('a');
        $body->appendChild($a);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('disabled');
        self::assertFalse($selector->matches($context, $input));
        $input->setAttribute('disabled', '');
        self::assertTrue($selector->matches($context, $input));

        self::assertFalse($selector->matches($context, $a));
        $a->setAttribute('disabled', '');
        self::assertFalse($selector->matches($context, $a));
    }

    public function testMatchesEnabled(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'text');
        $body->appendChild($input);
        $a = $doc->createElement('a');
        $body->appendChild($a);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('enabled');
        self::assertTrue($selector->matches($context, $input));
        $input->setAttribute('disabled', '');
        self::assertFalse($selector->matches($context, $input));

        self::assertFalse($selector->matches($context, $a));
        $a->setAttribute('disabled', '');
        self::assertFalse($selector->matches($context, $a));
    }

    public function testMatchesRequired(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'text');
        $body->appendChild($input);
        $a = $doc->createElement('a');
        $body->appendChild($a);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('required');
        self::assertFalse($selector->matches($context, $input));
        $input->setAttribute('required', '');
        self::assertTrue($selector->matches($context, $input));

        self::assertFalse($selector->matches($context, $a));
        $a->setAttribute('required', '');
        self::assertFalse($selector->matches($context, $a));
    }

    public function testMatchesOptional(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'text');
        $body->appendChild($input);
        $a = $doc->createElement('a');
        $body->appendChild($a);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('optional');
        self::assertTrue($selector->matches($context, $input));
        $input->setAttribute('required', '');
        self::assertFalse($selector->matches($context, $input));

        self::assertFalse($selector->matches($context, $a));
        $a->setAttribute('required', '');
        self::assertFalse($selector->matches($context, $a));
    }

    public function testMatchesReadWrite(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'text');
        $body->appendChild($input);
        $a = $doc->createElement('a');
        $body->appendChild($a);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('read-write');
        self::assertTrue($selector->matches($context, $input));
        $input->setAttribute('readonly', '');
        self::assertFalse($selector->matches($context, $input));

        self::assertFalse($selector->matches($context, $a));
        $a->setAttribute('contenteditable', '');
        self::assertTrue($selector->matches($context, $a));
    }

    public function testMatchesReadOnly(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'text');
        $body->appendChild($input);
        $a = $doc->createElement('a');
        $body->appendChild($a);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('read-only');
        self::assertFalse($selector->matches($context, $input));
        $input->setAttribute('readonly', '');
        self::assertTrue($selector->matches($context, $input));

        self::assertTrue($selector->matches($context, $a));
        $a->setAttribute('contenteditable', '');
        self::assertFalse($selector->matches($context, $a));
    }

    public function testMatchesChecked(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'checkbox');
        $body->appendChild($input);
        $a = $doc->createElement('a');
        $body->appendChild($a);
        $select = $doc->createElement('select');
        $body->appendChild($select);
        $option = $doc->createElement('option');
        $select->appendChild($option);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('checked');
        self::assertFalse($selector->matches($context, $input));
        $input->setAttribute('checked', '');
        self::assertTrue($selector->matches($context, $input));

        self::assertFalse($selector->matches($context, $a));
        $a->setAttribute('checked', '');
        self::assertFalse($selector->matches($context, $a));

        self::assertFalse($selector->matches($context, $option));
        $option->setAttribute('selected', '');
        self::assertTrue($selector->matches($context, $option));
    }

    public function testMatchesIndeterminate(): void
    {
        $doc = new \DOMDocument();
        $root = $doc->createElement('html');
        $doc->appendChild($root);
        $body = $doc->createElement('body');
        $root->appendChild($body);
        $radio1 = $doc->createElement('input');
        $radio1->setAttribute('type', 'radio');
        $radio1->setAttribute('name', 'group-a');
        $body->appendChild($radio1);
        $radio2 = $doc->createElement('input');
        $radio2->setAttribute('type', 'radio');
        $radio2->setAttribute('name', 'group-a');
        $body->appendChild($radio2);
        $a = $doc->createElement('a');
        $body->appendChild($a);
        $progress = $doc->createElement('progress');
        $body->appendChild($progress);

        $scope = $root;
        $nsLookup = [];
        $context = new DomNodeMatchContext($root, $scope, $nsLookup);

        $selector = new InputPseudoClass('indeterminate');
        self::assertTrue($selector->matches($context, $radio1));
        self::assertTrue($selector->matches($context, $radio2));
        $radio1->setAttribute('checked', '');
        self::assertFalse($selector->matches($context, $radio1));
        self::assertFalse($selector->matches($context, $radio2));

        self::assertFalse($selector->matches($context, $a));

        self::assertTrue($selector->matches($context, $progress));
        $progress->setAttribute('value', '12');
        self::assertFalse($selector->matches($context, $progress));
    }
}
