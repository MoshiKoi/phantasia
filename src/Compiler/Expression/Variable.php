<?php

namespace Phantasia\Compiler\Expression;

final class Variable extends Expression
{
    public function __construct(public string $name)
    {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitVariable($this);
    }
}