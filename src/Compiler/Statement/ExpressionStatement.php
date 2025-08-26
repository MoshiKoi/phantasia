<?php

namespace Phantasia\Compiler\Statement;

use Phantasia\Compiler\Expression\Expression;

final class ExpressionStatement extends Statement
{
    public function __construct(public readonly Expression $expression)
    {
    }

    public function accept(StatementVisitor $visitor): mixed
    {
        return $visitor->visitExpressionStatement($this);
    }
}