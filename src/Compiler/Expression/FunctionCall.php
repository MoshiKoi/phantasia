<?php

namespace Phantasia\Compiler\Expression;

final class FunctionCall extends Expression
{
    /**
     * @param Expression[] $parameters
     */
    public function __construct(public Expression $function, public array $parameters)
    {
    }

    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitFunctionCall($this);
    }
}