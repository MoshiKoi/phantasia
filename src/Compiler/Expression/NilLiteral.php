<?php

namespace Phantasia\Compiler\Expression;

class NilLiteral extends Expression
{
    public function accept(ExpressionVisitor $visitor): mixed
    {
        return $visitor->visitNilLiteral($this);
    }
}