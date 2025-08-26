<?php

namespace Phantasia\Compiler\Expression;

abstract class Expression
{
    /**
     * @template T
     * @param ExpressionVisitor<T> $visitor
     * @return T
     */
    abstract public function accept(ExpressionVisitor $visitor): mixed;
}
