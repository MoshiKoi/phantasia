<?php

declare(strict_types=1);

namespace Phantasia\Compiler\Tokenization;

use Exception;
use IntlChar;
use IntlCodePointBreakIterator;
use Iterator;

/**
 * @implements Iterator<Token>
 */
final class Tokenizer implements Iterator
{
    /**
     * @var array<string, TokenType>
     */
    private const array KEYWORDS = [
        'let' => TokenType::KwLet,
        'fn' => TokenType::KwFn,
        'end' => TokenType::KwEnd,
        'if' => TokenType::KwIf,
        'else' => TokenType::KwElse,
        'elseif' => TokenType::KwElseIf,
        'while' => TokenType::KwWhile,
        'true' => TokenType::KwTrue,
        'false' => TokenType::KwFalse,
        'nil' => TokenType::KwNil,
        'return' => TokenType::KwReturn,
    ];

    private $position;
    private $iterator;
    private $token;

    public function __construct(string $chars)
    {
        $this->iterator = IntlCodePointBreakIterator::createCodePointInstance();
        $this->iterator->setText($chars);
    }

    public function current(): Token
    {
        assert($this->token !== null);
        return $this->token;
    }

    public function key(): null
    {
        return null;
    }

    public function next(): void
    {
        $this->skipWhitespace();

        if ($this->char() === IntlCodePointBreakIterator::DONE) {
            $this->token = null;
            return;
        }

        if (IntlChar::isIDStart($this->char()) || $this->char() === mb_ord('_')) {
            $this->nextIdentifier();
            return;
        }

        if ($this->char() >= mb_ord('0') && $this->char() <= mb_ord('9')) {
            $this->nextNumber();
            return;
        }

        if ($this->char() === mb_ord('"')) {
            $this->nextString();
            return;
        }

        match ($this->char()) {
            mb_ord('(') => $this->nextSymbol(TokenType::LParen),
            mb_ord(')') => $this->nextSymbol(TokenType::RParen),
            mb_ord('[') => $this->nextSymbol(TokenType::LBrack),
            mb_ord(']') => $this->nextSymbol(TokenType::RBrack),
            mb_ord(';') => $this->nextSymbol(TokenType::Semicolon),
            mb_ord(',') => $this->nextSymbol(TokenType::Comma),
            mb_ord('+') => $this->nextSymbol(TokenType::Plus),
            mb_ord('-') => $this->nextSymbol(TokenType::Minus),
            mb_ord('*') => $this->nextSymbol(TokenType::Asterisk),
            mb_ord('/') => $this->nextSymbol(TokenType::Slash),
            mb_ord('%') => $this->nextSymbol(TokenType::Modulo),
            mb_ord('.') => $this->nextSymbol(TokenType::Dot),
            mb_ord('<') => $this->nextChar() === mb_ord('=')
            ? $this->nextSymbol(TokenType::LessEqual)
            : $this->token = new Token(TokenType::Less),
            mb_ord('>') => $this->nextChar() === mb_ord('=')
            ? $this->nextSymbol(TokenType::GreaterEqual)
            : $this->token = new Token(TokenType::Greater),
            mb_ord('=') => $this->nextChar() === mb_ord('=')
            ? $this->nextSymbol(TokenType::Equal)
            : $this->token = new Token(TokenType::Assign),
            default => throw new Exception('Unexpected character: ' . mb_chr($this->char())),
        };
    }

    public function rewind(): void
    {
        $this->position = 0;
        $this->iterator->first();
        $this->iterator->next();
        $this->next();
    }

    public function valid(): bool
    {
        return $this->token !== null;
    }

    private function skipWhitespace()
    {
        while (IntlChar::isUWhiteSpace($this->char())) {
            $this->nextChar();
        }
    }

    private function nextIdentifier()
    {
        $start = $this->position;

        while (IntlChar::isIDPart($this->char())) {
            $this->nextChar();
        }

        $lexeme = substr($this->iterator->getText(), $start, $this->position - $start);
        $keyword = self::KEYWORDS[$lexeme] ?? null;

        $this->token = $keyword !== null
            ? new Token($keyword)
            : new Token(TokenType::Identifier, $lexeme);
    }

    private function nextNumber()
    {
        $start = $this->position;

        while ($this->char() >= mb_ord('0') && $this->char() <= mb_ord('9')) {
            $this->nextChar();
        }
        if ($this->char() === mb_ord('.')) {
            $this->nextChar();
            while ($this->char() >= mb_ord('0') && $this->char() <= mb_ord('9')) {
                $this->nextChar();
            }
        }

        $value = floatval(substr($this->iterator->getText(), $start, $this->position - $start));
        $this->token = new Token(TokenType::Number, $value);
    }

    private function nextString()
    {
        $this->nextChar();
        $start = $this->position;

        while (
            !in_array(
                $this->char(),
                [mb_ord('"'), mb_ord("\n"), IntlCodePointBreakIterator::DONE]
            )
        ) {
            $this->nextChar();
        }

        $text = substr($this->iterator->getText(), $start, $this->position - $start);

        if ($this->char() !== mb_ord('"')) {
            $text .= "\n";
        }

        $this->nextChar();
        $this->token = new Token(TokenType::String, $text);
    }

    private function nextSymbol(TokenType $type)
    {
        $this->token = new Token($type);
        $this->nextChar();
    }

    private function nextChar(): int
    {
        $this->position = $this->iterator->current();
        $this->iterator->next();
        return $this->iterator->getLastCodePoint();
    }

    private function char(): int
    {
        return $this->iterator->getLastCodePoint();
    }
}