# Cici

Cici is a PHP library which lets you use CSS selector to locate elements in an HTML document.

## Installation

```bash
composer require manychois/cici
```

## Examples

### Locate an element

```php
$doc = new \DOMDocument();
$doc->loadHTML('<div id="div-1">Hello world!</div>');
$query = new \Manychois\Cici\DomQuery();
$element = $query->query($doc, '#div-1');
echo $element->textContent . \PHP_EOL;
// Output: Hello world!
```

### Locate an element with a namespace URI

```php
$xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
  width="100px" height="100px" viewBox="0 0 100 100">
  <circle cx="50" cy="50" r="40" />
</svg>
XML;
$doc = new \DOMDocument();
$doc->loadXML($xml);
$query = new \Manychois\Cici\DomQuery();
$element = $query->query($doc, 'svg|circle', ['svg' => 'http://www.w3.org/2000/svg']);
echo $element->getAttribute('r') . PHP_EOL;
// Output: 40
```

### Locate multiple elements

```php
$html = <<<'HTML'
<ul>
    <li>Item 1</li>
    <li>Item 2</li>
    <li>Item 3</li>
    <li>Item 4</li>
</ul>
HTML;
$doc = new \DOMDocument();
$doc->loadXML($html);
$query = new \Manychois\Cici\DomQuery();
foreach ($query->queryAll($doc, 'li:nth-child(even)') as $element) {
    echo $element->textContent . PHP_EOL;
}
// Output:
// Item 2
// Item 4
```

## Supported CSS selectors

Selector | Example | Notes
--- | --- | ---
Attribute selector | `[attr="value" i]` | Namespace prefix is supported.<br>Case-sensitivity modifier is supported.
Class selector | `.primary` |
ID selector | `#main` |
Type selector | `div` | Namespace prefix is supported.
Universal selector | `*` | Namespace prefix is supported.
Pseudo-class selector | `:nth-child(2n+1 of .selected)` | Check the section below for supported pseudo-classes.
Compound selector | `div.active` |
Child combinator | `ul > li` |
Descendant combinator | `nav a` |
Next-sibling combinator | `p + img` |
Subsequent-sibling combinator | `div ~ img` |
Selector list | `ul, ol` |

### Supported pseudo-classes

List in alphabetical order:

- `:any-link`
- `:checked`
- `:disabled`
- `:empty`
- `:enabled`
- `:first-child`
- `:first-of-type`
- `:has`
- `:indeterminate`
- `:is`
- `:last-child`
- `:last-of-type`
- `:not`
- `:nth-child`
- `:nth-last-child`
- `:nth-last-of-type`
- `:nth-of-type`
- `:only-child`
- `:only-of-type`
- `:optional`
- `:read-only`
- `:read-write`
- `:required`
- `:root`
- `:scope`
- `:where`

## Unsupported CSS selectors

- All pseudo-elements, e.g. `::before`.
- Column combinator, i.e. `||`.
- All pseudo-classes which are not listed in the previous section. Basically anything which involes user interaction, e.g. `:hover`, will never be supported.
