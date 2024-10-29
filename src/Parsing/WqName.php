<?php

declare(strict_types=1);

namespace Manychois\Cici\Parsing;

use Manychois\Cici\Tokenization\Tokens\AbstractToken;

/**
 * Represents a qualified name which accepts a wildcard syntax.
 */
class WqName implements \JsonSerializable, \Stringable
{
    public readonly bool $prefixSpecified;
    public readonly ?string $prefix;
    public readonly string $localName;

    /**
     * Creates a new instance of the qualified name.
     *
     * @param bool        $prefixSpecified Whether the namespace prefix is specified.
     * @param string|null $prefix          The namespace prefix.
     * @param string      $localName       The local name.
     */
    public function __construct(bool $prefixSpecified, ?string $prefix, string $localName)
    {
        \assert($prefixSpecified || $prefix === null);
        \assert($prefix !== '');
        \assert($localName !== '');

        $this->prefixSpecified = $prefixSpecified;
        $this->prefix = $prefix;
        $this->localName = $localName;
    }

    #region implements \JsonSerializable

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        if ($this->prefixSpecified) {
            return [
                'localName' => $this->localName,
                'prefix' => $this->prefix,
            ];
        }

        return ['localName' => $this->localName];
    }

    #endregion implements \JsonSerializable

    #region implements \Stringable

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        $localName = $this->localName === '*' ? '*' : AbstractToken::escapeIdent($this->localName);
        if ($this->prefixSpecified) {
            $prefix = match ($this->prefix) {
                '*' => '*',
                null => '',
                default => AbstractToken::escapeIdent($this->prefix),
            };

            return $prefix . '|' . $localName;
        }

        return $localName;
    }

    #endregion implements \Stringable
}
