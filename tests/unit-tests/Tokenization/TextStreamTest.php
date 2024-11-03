<?php

declare(strict_types=1);

namespace Manychois\CiciTests\UnitTests\Tokenization;

use Manychois\Cici\Exceptions\ParseExceptionCollection;
use Manychois\Cici\Tokenization\TextStream;
use PHPUnit\Framework\TestCase;

class TextStreamTest extends TestCase
{
    public function testConstructor(): void
    {
        $errors = new ParseExceptionCollection();
        $text = "\r\n";
        $text = new TextStream($text, $errors);
        self::assertEquals(1, $text->length);
        $ch = $text->consume();
        self::assertEquals("\n", $ch);
        self::assertFalse($text->hasMore());

        $text = "\0";
        $text = new TextStream($text, $errors);
        self::assertEquals(3, $text->length);
        $ch = '';
        while ($text->hasMore()) {
            $ch .= $text->consume();
        }
        self::assertEquals("\u{FFFD}", $ch);
    }

    public function testConsume_eofThrowsException(): void
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The end of the text stream has been reached.');
        $errors = new ParseExceptionCollection();
        $text = new TextStream('', $errors);
        $text->consume();
    }

    public function testMatchRegex(): void
    {
        $errors = new ParseExceptionCollection();
        $text = 'abc123def';
        $text = new TextStream($text, $errors);
        $result = $text->matchRegex('/\d+/', true);
        self::assertTrue($result->success);
        self::assertEquals('123', $result->value);
        self::assertEquals(3, $result->offset);
    }

    public function testRecordParseException(): void
    {
        $errors = new ParseExceptionCollection();
        $text = new TextStream('港', $errors);
        $text->recordParseException();
        self::assertCount(1, $errors);
        $error = $errors->get(0);
        self::assertEquals('Unexpected character "港".', $error->getMessage());
        self::assertEquals(0, $error->position);

        while ($text->hasMore()) {
            $text->consume();
        }
        $text->recordParseException('Custom message.');
        self::assertCount(2, $errors);
        $error = $errors->get(1);
        self::assertEquals('Custom message.', $error->getMessage());
        self::assertEquals(3, $error->position);

        $text->recordParseException();
        self::assertCount(3, $errors);
        $error = $errors->get(2);
        self::assertEquals('Unexpected end of input.', $error->getMessage());
        self::assertEquals(3, $error->position);
    }
}
