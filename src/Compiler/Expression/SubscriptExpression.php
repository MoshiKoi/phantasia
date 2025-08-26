<?php

namespace Phantasia\Compiler\Expression;

final class SubscriptExpression extends Expression
{
    public function __construct(public Expression $expression, public Expression $subscript)
    {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitSubscript($this);
    }
}