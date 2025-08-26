<?php

namespace Phantasia\Compiler\Statement;

use Phantasia\Compiler\Expression\Expression;

class ReturnStatement extends Statement
{
    public function __construct(public Expression $expression)
    {
    }

    public function accept(StatementVisitor $visitor): mixed
    {
        return $visitor->visitReturn($this);
    }
}