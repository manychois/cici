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
     * @param int    $offset The offset of the token.
     */
    public function __construct(Symbol $symbol, int $offset)
    {
        parent::__construct($offset);

        $this->value = $symbol;
    }

    #region extends AbstractToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return $this->value->value;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->value->value;
    }

    #endregion extends AbstractToken
}
