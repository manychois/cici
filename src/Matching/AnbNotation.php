<?php

declare(strict_types=1);

namespace Manychois\Cici\Matching;

/**
 * Represents the `an+b` notation.
 */
class AnbNotation implements \JsonSerializable, \Stringable
{
    // either 'odd', 'even' or empty.
    public readonly int $a;
    public readonly int $b;
    private readonly string $keyword;

    /**
     * Creates a new instance of the AnbNotation class.
     *
     * @param int    $a       The `a` value.
     * @param int    $b       The `b` value.
     * @param string $keyword Either 'odd', 'even' or empty.
     */
    public function __construct(int $a, int $b, string $keyword = '')
    {
        \assert(
            $keyword === '' ||
                $keyword === 'odd' && $a === 2 && $b === 1 ||
                $keyword === 'even' && $a === 2 && $b === 0
        );
        $this->a = $a;
        $this->b = $b;
        $this->keyword = $keyword;
    }

    /**
     * Determines whether the An+B formula matches the given index.
     *
     * @param int $index The 1-based index.
     *
     * @return bool True if the An+B formula matches the given index; otherwise, false.
     */
    public function matches(int $index): bool
    {
        if ($index <= 0) {
            return false;
        }
        if ($this->a === 0) {
            return $index === $this->b;
        }

        return (($index - $this->b) % $this->a === 0) && (($index - $this->b) / $this->a >= 0);
    }

    #region implements \JsonSerializable

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        $json = [
            'a' => $this->a,
            'b' => $this->b,
        ];
        if ($this->keyword !== '') {
            $json['keyword'] = $this->keyword;
        }

        return $json;
    }

    #endregion implements \JsonSerializable

    #region implements \Stringable

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if ($this->keyword !== '') {
            return $this->keyword;
        }

        if ($this->a === 0) {
            return \strval($this->b);
        }

        $an = match ($this->a) {
            1 => 'n',
            -1 => '-n',
            default => $this->a . 'n',
        };

        $b = $this->b === 0 ? '' : ($this->b > 0 ? '+' : '') . $this->b;

        return $an . $b;
    }

    #endregion implements \Stringable
}
