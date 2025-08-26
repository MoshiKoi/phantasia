<?php

namespace Phantasia\Compiler\Expression;

final class Assignment extends Expression
{
    public function __construct(public Variable $variable, public Expression $expression)
    {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitAssignment($this);
    }
}
