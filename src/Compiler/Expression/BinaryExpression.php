<?php

namespace Phantasia\Compiler\Expression;

final class BinaryExpression extends Expression
{
    public function __construct(
        public BinaryOperator $operator,
        public Expression $lhs,
        public Expression $rhs
    ) {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitBinaryExpression($this);
    }
}