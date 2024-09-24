<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a unicode range token.
 */
class UnicodeRangeToken extends AbstractToken
{
    /**
     * Creates a new instance of the UnicodeRangeToken class.
     *
     * @param int $start  The start of the range.
     * @param int $end    The end of the range.
     * @param int $offset The offset of the token.
     */
    public function __construct(public readonly int $start, public readonly int $end, int $offset)
    {
        parent::__construct($offset);
    }

    #region extends AbstractToken

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'end' => $this->end,
            'start' => $this->start,
            'type' => 'unicode-range',

        ];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if ($this->start === $this->end) {
            return \sprintf('U+%X', $this->start);
        }

        return \sprintf('U+%X-%X', $this->start, $this->end);
    }

    #endregion extends AbstractToken
}
