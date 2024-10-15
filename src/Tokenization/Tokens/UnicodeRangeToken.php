<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization\Tokens;

/**
 * Represents a unicode range token.
 */
class UnicodeRangeToken extends AbstractToken
{
    public readonly int $start;
    public readonly int $end;

    /**
     * Creates a new instance of the UnicodeRangeToken class.
     *
     * @param int $start  The start of the range.
     * @param int $end    The end of the range.
     * @param int $offset The string position at which the token starts.
     * @param int $length The byte length of the token.
     */
    public function __construct(int $start, int $end, int $offset, int $length)
    {
        parent::__construct($offset, $length);

        $this->start = $start;
        $this->end = $end;
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
