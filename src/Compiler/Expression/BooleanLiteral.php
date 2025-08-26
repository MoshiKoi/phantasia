<?php

namespace Phantasia\Compiler\Expression;

class BooleanLiteral extends Expression
{
    public function __construct(public bool $value)
    {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitBooleanLiteral($this);
    }
}