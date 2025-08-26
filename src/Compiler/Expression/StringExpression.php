<?php

namespace Phantasia\Compiler\Expression;

final class StringExpression extends Expression
{

    public function __construct(public string $value)
    {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitStringExpression($this);
    }
}