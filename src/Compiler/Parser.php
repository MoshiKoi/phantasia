<?php

namespace Phantasia\Compiler;

use Phantasia\Compiler\Expression\Assignment;
use Phantasia\Compiler\Statement\IfStatement;
use Phantasia\Compiler\Statement\WhileLoop;
use Phantasia\Compiler\Tokenization\{Token, TokenType};
use Phantasia\Compiler\Expression\{
    Expression,
    NumberExpression,
    StringExpression,
    BooleanLiteral,
    NilLiteral,
    BinaryOperator,
    BinaryExpression,
    Variable,
    FunctionCall,
    SubscriptExpression,
};
use Phantasia\Compiler\Statement\{
    Statement,
    DeclarationStatement,
    FunctionDeclaration,
    ReturnStatement,
    ExpressionStatement,
};

use Iterator;

class Parser
{
    /** @var Iterator<Token> */
    private Iterator $iterator;

    /**
     * @param Iterator<Token> $iterator
     */
    public function __construct(Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @return Statement[]
     */
    public function parse(): array
    {
        $this->iterator->rewind();
        $statements = [];
        while ($this->iterator->valid()) {
            $statements[] = $this->parseStatement();
        }
        return $statements;
    }

    private function parseStatement(): Statement
    {
        return match ($this->iterator->current()->type) {
            TokenType::KwLet => $this->parseDeclarationStatement(),
            TokenType::KwFn => $this->parseFunctionDeclaration(),
            TokenType::KwReturn => $this->parseReturnStatement(),
            TokenType::KwWhile => $this->parseWhileLoop(),
            TokenType::KwIf => $this->parseIfStatement(),
            default => $this->parseExpressionStatement(),
        };
    }

    private function parseDeclarationStatement(): Statement
    {
        $this->eat(TokenType::KwLet);
        $name = $this->eat(TokenType::Identifier);

        if ($this->currentType(TokenType::Assign)) {
            $this->eat(TokenType::Assign);
            $expression = $this->parseExpression();
        }

        return new DeclarationStatement(new Variable($name), $expression ?? new NilLiteral);
    }

    private function parseFunctionDeclaration(): Statement
    {
        $this->eat(TokenType::KwFn);
        $name = $this->eat(TokenType::Identifier);

        $parameters = [];

        $this->eat(TokenType::LParen);

        if ($this->currentType(TokenType::Identifier)) {
            $parameters[] = new Variable($this->eat(TokenType::Identifier));

            while ($this->currentType(TokenType::Comma)) {
                $this->eat(TokenType::Comma);
                $parameters[] = new Variable($this->eat(TokenType::Identifier));
            }
        }

        $this->eat(TokenType::RParen);

        $body = [];

        while ($this->iterator->valid() && $this->iterator->current()->type !== TokenType::KwEnd) {
            $body[] = $this->parseStatement();
        }

        $this->eat(TokenType::KwEnd);

        return new FunctionDeclaration(new Variable($name), $parameters, $body);
    }

    private function parseReturnStatement(): Statement
    {
        $this->eat(TokenType::KwReturn);

        if ($this->iterator->valid()) {
            return new ReturnStatement($this->parseExpression());
        } else {
            return new ReturnStatement(new NilLiteral);
        }
    }

    private function parseIfStatement(): Statement
    {
        $this->eat(TokenType::KwIf);
        return $this->parseIfTail();
    }

    private function parseIfTail()
    {
        $condition = $this->parseExpression();
        $body = $this->parseBody([TokenType::KwEnd, TokenType::KwElse, TokenType::KwElseIf]);

        if ($this->currentType(TokenType::KwElseIf)) {
            $this->eat(TokenType::KwElseIf);
            return new IfStatement($condition, $body, [$this->parseIfTail()]);
        } else if ($this->currentType(TokenType::KwElse)) {
            $this->eat(TokenType::KwElse);
            $elseBody = $this->parseBody([TokenType::KwEnd]);
            $this->eat(TokenType::KwEnd);
            return new IfStatement($condition, $body, $elseBody);
        } else {
            $this->eat(TokenType::KwEnd);
            return new IfStatement($condition, $body, []);
        }
    }

    private function parseWhileLoop(): Statement
    {
        $this->eat(TokenType::KwWhile);

        $condition = $this->parseExpression();
        $body = $this->parseBody([TokenType::KwEnd]);
        $this->eat(TokenType::KwEnd);

        return new WhileLoop($condition, $body);
    }

    private function parseBody(array $end_tokens): array
    {
        $body = [];
        while ($this->iterator->valid() && !in_array($this->iterator->current()->type, $end_tokens)) {
            $body[] = $this->parseStatement();
        }
        return $body;
    }

    private function parseExpressionStatement(): Statement
    {
        return new ExpressionStatement($this->parseExpression());
    }

    private function parseExpression(): Expression
    {
        return $this->parseAssignment();
    }

    private function parseAssignment()
    {
        $lhs = $this->parseBinaryExpression();
        if ($lhs instanceof Variable && $this->currentType(TokenType::Assign)) {
            $this->eat(TokenType::Assign);
            $rhs = $this->parseAssignment();
            return new Assignment($lhs, $rhs);
        }
        return $lhs;
    }

    private function parseBinaryExpression(): Expression
    {
        $lhs = $this->parsePrefixExpression();
        return $this->parseBinaryExpressionRHS($lhs, 0);
    }

    private function parseBinaryExpressionRHS(Expression $lhs, int $exprPrecedence): Expression
    {
        while ($this->iterator->valid()) {
            $currentOperator = $this->getBinaryOperator($this->iterator->current()->type);
            $currentPrecedence = $this->getPrecedence($currentOperator);

            if ($currentPrecedence < $exprPrecedence) {
                break;
            }

            $this->iterator->next();

            $rhs = $this->parsePrefixExpression();

            $nextOperator = $this->iterator->valid()
                ? $this->getBinaryOperator($this->iterator->current()->type)
                : null;

            $nextPrecedence = $this->getPrecedence($nextOperator);

            if ($currentPrecedence < $nextPrecedence) {
                $rhs = $this->parseBinaryExpressionRHS($rhs, $currentPrecedence + 1);
            }

            $lhs = new BinaryExpression($currentOperator, $lhs, $rhs);
        }

        return $lhs;
    }

    private function parsePrefixExpression(): Expression
    {
        $this->throwIfEndOfInput("Expected an expression");
        return match ($this->iterator->current()->type) {
            default => $this->parsePostfixExpression(),
        };
    }

    private function parsePostfixExpression(): Expression
    {
        $expr = $this->parsePrimary();
        while ($this->iterator->valid()) {
            switch ($this->iterator->current()->type) {
                case TokenType::LParen:
                    $expr = new FunctionCall($expr, $this->parseFunctionParameters());
                    break;
                case TokenType::LBrack:
                    $this->eat(TokenType::LBrack);
                    $subscript = $this->parseExpression();
                    $this->eat(TokenType::RBrack);
                    $expr = new SubscriptExpression($expr, $subscript);
                    break;
                case TokenType::Dot:
                    $this->eat(TokenType::Dot);
                    $expr = new SubscriptExpression($expr, new StringExpression($this->eat(TokenType::Identifier)));
                    break;
                default:
                    break 2;
            }
        }
        return $expr;
    }

    /**
     * @return Expression[]
     */
    private function parseFunctionParameters(): array
    {
        $parameters = [];
        $this->eat(TokenType::LParen);

        if ($this->currentType(TokenType::RParen)) {
            $this->eat(TokenType::RParen);
            return $parameters;
        }

        $parameters[] = $this->parseExpression();

        while ($this->currentType(TokenType::Comma)) {
            $this->eat(TokenType::Comma);
            $parameters[] = $this->parseExpression();
        }

        $this->eat(TokenType::RParen);

        return $parameters;
    }

    private function parsePrimary(): Expression
    {
        $this->throwIfEndOfInput("expected a primary expression");
        return match ($this->iterator->current()->type) {
            TokenType::LParen => $this->parseGroup(),
            TokenType::Number => new NumberExpression($this->eat(TokenType::Number)),
            TokenType::String => new StringExpression($this->eat(TokenType::String)),
            TokenType::KwNil => $this->parseLiteral(new NilLiteral),
            TokenType::KwTrue => $this->parseLiteral(new BooleanLiteral(true)),
            TokenType::KwFalse => $this->parseLiteral(new BooleanLiteral(false)),
            TokenType::Identifier => new Variable($this->eat(TokenType::Identifier)),
            default => throw new SyntaxException('Unexpected token ' . $this->iterator->current()->type->name),
        };
    }

    private function parseLiteral(Expression $literal): Expression
    {
        $this->iterator->next();
        return $literal;
    }

    private function parseGroup(): Expression
    {
        $this->eat(TokenType::LParen);
        $expression = $this->parseExpression();
        $this->eat(TokenType::RParen);
        return $expression;
    }

    private function eat(TokenType $type): mixed
    {
        $this->throwIfEndOfInput("expected $type->name");

        $token = $this->iterator->current();
        if ($token->type === $type) {
            $value = $token->value;
            $this->iterator->next();
            return $value;
        } else {
            throw new SyntaxException("Unexpected token: expected $type->name, got {$token->type->name}");
        }
    }

    private function throwIfEndOfInput(string $message)
    {
        if (!$this->iterator->valid()) {
            throw new SyntaxException("Unexpected end of input: $message");
        }
    }

    private function currentType(TokenType $type): bool
    {
        return $this->iterator->valid() && $this->iterator->current()->type === $type;
    }

    private function getPrecedence(?BinaryOperator $tokenType): int
    {
        return match ($tokenType) {
            BinaryOperator::Less, BinaryOperator::Greater,
            BinaryOperator::LessEqual, BinaryOperator::GreaterEqual,
            BinaryOperator::Equal, BinaryOperator::NotEqual => 10,
            BinaryOperator::Plus, BinaryOperator::Minus => 20,
            BinaryOperator::Multiply, BinaryOperator::Divide, BinaryOperator::Modulo => 30,
            default => -1,
        };
    }

    private function getBinaryOperator(TokenType $tokenType): ?BinaryOperator
    {
        return match ($tokenType) {
            TokenType::Less => BinaryOperator::Less,
            TokenType::Greater => BinaryOperator::Greater,
            TokenType::LessEqual => BinaryOperator::LessEqual,
            TokenType::GreaterEqual => BinaryOperator::GreaterEqual,
            TokenType::Equal => BinaryOperator::Equal,
            TokenType::NotEqual => BinaryOperator::NotEqual,
            TokenType::Plus => BinaryOperator::Plus,
            TokenType::Minus => BinaryOperator::Minus,
            TokenType::Asterisk => BinaryOperator::Multiply,
            TokenType::Slash => BinaryOperator::Divide,
            TokenType::Modulo => BinaryOperator::Modulo,
            default => null,
        };
    }
}
