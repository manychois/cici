<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization;

use Manychois\Cici\Exceptions\ParseException;
use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Utilities\RegexResult;

/**
 * Represents a stream of characters for tokenization.
 */
final class TextStream
{
    /**
     * The length of the text stream in number of bytes.
     */
    public readonly int $length;
    /**
     * The current position in the text stream.
     */
    public int $position = 0;
    /**
     * The normalized text from the input.
     */
    public readonly string $text;
    public readonly ParseExceptionCollection $errors;

    /**
     * Creates a new instance of the TextStream class.
     *
     * @param string                   $text   The text to tokenize.
     * @param ParseExceptionCollection $errors The collection to hold parsing errors.
     */
    public function __construct(string $text, ParseExceptionCollection $errors)
    {
        $this->errors = $errors;

        // preprocessing the input stream
        $text = \str_replace("\0", "\u{FFFD}", $text);
        $text = \preg_replace('/\r\n?|\f/', "\n", $text);
        \assert(\is_string($text));

        $this->text = $text;
        $this->length = \strlen($text);
    }

    /**
     * Consumes the next single-byte character from the text stream.
     *
     * @return string The consumed character.
     */
    public function consume(): string
    {
        if ($this->position >= $this->length) {
            throw new \OutOfBoundsException('The end of the text stream has been reached.');
        }

        $ch = $this->text[$this->position];
        $this->position++;

        return $ch;
    }

    /**
     * Checks whether there is more character to consume.
     *
     * @return bool `true` if there is more character to consume; otherwise, `false`.
     */
    public function hasMore(): bool
    {
        return $this->position < $this->length;
    }

    /**
     * Matches the specified regular expression pattern against the text stream.
     *
     * @param string $pattern       The regular expression pattern.
     * @param bool   $captureOffset Whether to capture the offset of the matched value.
     *
     * @return RegexResult The result of the match.
     */
    public function matchRegex(string $pattern, bool $captureOffset = false): RegexResult
    {
        return RegexResult::matches($pattern, $this->text, $this->position, $captureOffset);
    }

    /**
     * Peeks the specified number of characters at the current position.
     * The current position is not changed.
     *
     * @param int $length The number of single-byte characters to peek.
     *
     * @return string The substring of the text stream.
     */
    public function peek(int $length): string
    {
        return \substr($this->text, $this->position, $length);
    }

    /**
     * Creates and stores a parse exception.
     *
     * @param string $message The error message.
     *
     * @return ParseException The created parse exception.
     */
    public function recordParseException(string $message = ''): ParseException
    {
        if ($message === '') {
            if ($this->position < $this->length) {
                $ch = \substr($this->text, $this->position, 4);
                $ch = \mb_substr($ch, 0, 1, 'UTF-8');
                $message = \sprintf('Unexpected character "%s".', $ch);
            } else {
                $message = 'Unexpected end of input.';
            }
        }

        $ex = new ParseException($message, $this->position);
        $this->errors->add($ex);

        return $ex;
    }

    /**
     * Skips any consecutive whitespace characters at the current position.
     */
    public function skipWhitespace(): void
    {
        if (\preg_match('/\\G[ \\t\\n]+/', $this->text, $matches, 0, $this->position) !== 1) {
            return;
        }

        $this->position += \strlen($matches[0]);
    }
}
