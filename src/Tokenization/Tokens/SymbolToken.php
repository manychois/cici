<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a symbol token.
 */
class SymbolToken extends AbstractToken
{
    public readonly Symbol $value;

    /**
     * Initializes a new instance of SymbolToken.
     *
     * @param Symbol $symbol The symbol.
     * @param int    $offset The string position at which the token starts.
     * @param int    $length The byte length of the token.
     */
    public function __construct(Symbol $symbol, int $offset, int $length)
    {
        parent::__construct($offset, $length);

        $this->value = $symbol;
    }

    #region extends AbstractToken

    /**
     * @inheritDoc
     */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        return $this->value->value;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function __toString(): string
    {
        return $this->value->value;
    }

    #endregion extends AbstractToken
}
