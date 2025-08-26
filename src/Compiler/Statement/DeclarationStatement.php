<?php

namespace Phantasia\Compiler\Statement;

use Phantasia\Compiler\Expression\{Expression, Variable};

class DeclarationStatement extends Statement
{
    public function __construct(public Variable $variable, public Expression $expression)
    {
    }

    public function accept(StatementVisitor $visitor): mixed
    {
        return $visitor->visitDeclarationStatement($this);
    }
}