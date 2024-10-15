<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Tokenization;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Tokenization\Tokens\IdentToken;
use Manychois\Cici\Tokenization\Tokens\WhitespaceToken;
use Manychois\Cici\Tokenization\TokenStream;
use PHPUnit\Framework\TestCase;

class TokenStreamTest extends TestCase
{
    public function testRecordParseException(): void
    {
        $errors = new ParseExceptionCollection();
        $tokenStream = new TokenStream([], $errors);
        $tokenStream->recordParseException('End of input!');
        $this->assertCount(1, $errors);
        $error = $errors->get(0);
        $this->assertSame('End of input!', $error->getMessage());
        $this->assertSame(0, $error->position);

        $errors = new ParseExceptionCollection();
        $token = new WhitespaceToken(20, 1);
        $tokenStream = new TokenStream([$token], $errors);
        $tokenStream->recordParseException('Unknown whitespace.');
        $this->assertCount(1, $errors);
        $error = $errors->get(0);
        $this->assertSame('Unknown whitespace.', $error->getMessage());
        $this->assertSame(20, $error->position);
    }

    public function testSkipWhitepace(): void
    {
        $errors = new ParseExceptionCollection();
        $ws1 = new WhitespaceToken(0, 1);
        $ws2 = new WhitespaceToken(1, 1);
        $id = new IdentToken('div', 2, 3);
        $tokenStream = new TokenStream([$ws1, $ws2, $id], $errors);
        $hasWs = $tokenStream->skipWhitespace();
        $this->assertTrue($hasWs);
        $this->assertSame(2, $tokenStream->position);
        $hasWs = $tokenStream->skipWhitespace();
        $this->assertFalse($hasWs);
        $this->assertSame(2, $tokenStream->position);
        $this->assertSame($id, $tokenStream->tryConsume());
    }
}
