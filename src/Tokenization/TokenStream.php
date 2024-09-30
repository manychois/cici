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
     * @param array<int,AbstractToken> $tokens The tokens to tokenize.
     * @param ParseExceptionCollection $errors The collection to hold parsing errors.
     */
    public function __construct(array $tokens, ParseExceptionCollection $errors)
    {
        $this->errors = $errors;
        $this->tokens = $tokens;
        $this->length = \count($tokens);
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
     * @param string $message  The error message.
     * @param int    $position The position of the error. If negative, the current string position is used.
     *
     * @return ParseException The created parse exception.
     */
    public function recordParseException(string $message, int $position = -1): ParseException
    {
        if ($position < 0) {
            if ($this->position < $this->length) {
                $position = $this->tokens[$this->position]->position;
            } else {
                $count = \count($this->tokens);
                $position = $count > 0 ? $this->tokens[$count - 1]->position : 0;
            }
        }
        $ex = new ParseException($message, $position);
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

    /**
     * Returns the first token searching from the current position to the end satisfies the given predicate.
     *
     * @param \Closure $predicate The predicate to check.
     *
     * @return AbstractToken|null The first token that satisfies the predicate, or `null` if no token satisfies the
     * predicate.
     *
     * @psalm-param \Closure(AbstractToken):bool $predicate
     */
    public function first(\Closure $predicate): ?AbstractToken
    {
        for ($i = $this->position; $i < $this->length; $i++) {
            if ($predicate($this->tokens[$i])) {
                return $this->tokens[$i];
            }
        }

        return null;
    }

    /**
     * Consumes the next token from the token stream, if there is any.
     *
     * @return AbstractToken|null The consumed token, or `null` if there is no more token.
     */
    public function tryConsume(): ?AbstractToken
    {
        if ($this->position >= $this->length) {
            return null;
        }

        $token = $this->tokens[$this->position];
        $this->position++;

        return $token;
    }
}
