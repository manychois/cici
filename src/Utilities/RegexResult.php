<?php

declare(strict_types=1);

namespace Manychois\Cici\Utilities;

/**
 * Represents the result of a regular expression match.
 */
final class RegexResult
{
    /**
     * Whether the regular expression matches the subject.
     */
    public readonly bool $success;
    /**
     * The matched value.
     */
    public readonly string $value;
    /**
     * The offset of the matched value in the subject.
     * If the offset is not available, it is -1.
     */
    public readonly int $offset;

    /**
     * Matches the specified regular expression pattern against the specified subject.
     *
     * @param string $pattern       The regular expression pattern.
     * @param string $subject       The subject to match against.
     * @param int    $offset        The offset in the subject at which to start the search.
     * @param bool   $captureOffset Whether to capture the offset of the matched value.
     *
     * @return self The result of the match.
     */
    public static function matches(string $pattern, string $subject, int $offset = 0, bool $captureOffset = false): self
    {
        /** @var array<int,mixed> $matches */
        $matches = [];
        $flag = $captureOffset ? \PREG_OFFSET_CAPTURE : 0;
        $return = @\preg_match($pattern, $subject, $matches, $flag, $offset);
        if ($return === false) {
            throw new \InvalidArgumentException('The regular expression pattern is invalid.');
        }
        $isMatch = $return === 1;
        if ($isMatch) {
            if ($captureOffset) {
                $matches = $matches[0];
                $value = $matches[0];
                $offset = $matches[1];
            } else {
                $value = $matches[0];
                $offset = -1;
            }
        } else {
            $value = '';
            $offset = -1;
        }
        \assert(\is_string($value));
        \assert(\is_int($offset));

        return new self($isMatch, $value, $offset);
    }

    /**
     * Initializes a new instance of the RegexResult class.
     *
     * @param bool   $isMatch Whether the regular expression matches the subject.
     * @param string $value   The matched value.
     * @param int    $offset  The offset of the matched value in the subject.
     */
    public function __construct(bool $isMatch, string $value, int $offset)
    {
        $this->success = $isMatch;
        $this->value = $value;
        $this->offset = $offset;
    }
}
