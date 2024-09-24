<?php

declare(strict_types=1);

namespace Manychois\Cici\Tokenization;

use Manychois\Cici\Exceptions\ParseException;
use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Tokenization\Tokens\AbstractToken;
use Manychois\Cici\Tokenization\Tokens\WhitespaceToken;

/**
 * Represents a stream of CSS tokens.
 */
class TokenStream
{
    /**
     * The length of the token stream.
     */
    public readonly int $length;
    /**
     * The collection to hold parsing errors.
     */
    public readonly ParseExceptionCollection $errors;
    /**
     * The length of the text being tokenized.
     */
    public readonly int $textLength;
    /**
     * The current position in the token stream.
     */
    public int $position = 0;
    /**
     * @var array<int,AbstractToken>
     */
    private readonly array $tokens;

    /**
     * Creates a new instance of the TokenStream class.
     *
     * @param array<int,AbstractToken> $tokens     The tokens to tokenize.
     * @param int                      $textLength The length of the text being tokenized.
     * @param ParseExceptionCollection $errors     The collection to hold parsing errors.
     */
    public function __construct(array $tokens, int $textLength, ParseExceptionCollection $errors)
    {
        $this->errors = $errors;
        $this->textLength = $textLength;
        $this->tokens = $tokens;
        $this->length = \count($tokens);
    }

    /**
     * Consumes the next token from the token stream.
     *
     * @return AbstractToken The consumed token.
     */
    public function consume(): AbstractToken
    {
        if ($this->position >= $this->length) {
            throw new \OutOfBoundsException('The end of the token stream has been reached.');
        }

        $token = $this->tokens[$this->position];
        $this->position++;

        return $token;
    }

    /**
     * Checks whether there is more token to consume.
     *
     * @return bool `true` if there is more token to consume; otherwise, `false`.
     */
    public function hasMore(): bool
    {
        return $this->position < $this->length;
    }

    /**
     * Creates and stores a parse exception.
     *
     * @param string $message The error message.
     *
     * @return ParseException The created parse exception.
     */
    public function recordParseException(string $message): ParseException
    {
        $token = $this->position < $this->length ? $this->tokens[$this->position] : null;
        $ex = new ParseException($message, $token?->offset ?? $this->textLength);
        $this->errors->add($ex);

        return $ex;
    }

    /**
     * Skips any whitespace token at the current position.
     *
     * @return bool `true` if a whitespace token was skipped; otherwise, `false`.
     */
    public function skipWhitespace(): bool
    {
        $hasWs = false;
        while ($this->position < $this->length) {
            $token = $this->tokens[$this->position];
            if (!($token instanceof WhitespaceToken)) {
                break;
            }
            $hasWs = true;
            $this->position++;
        }

        return $hasWs;
    }
}
