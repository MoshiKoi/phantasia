<?php

namespace Phantasia\Compiler\Expression;

final class NumberExpression extends Expression
{
    public function __construct(public float $value)
    {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitNumberExpression($this);
    }
}