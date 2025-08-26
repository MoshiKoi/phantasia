<?php

namespace Phantasia\Compiler\Statement;

use Phantasia\Compiler\Expression\Expression;

class WhileLoop extends Statement
{
    
    /**
     * @param Expression $condition
     * @param Statement[] $body
     */
    public function __construct(public Expression $condition, public array $body)
    {
    }

    public function accept(StatementVisitor $visitor): mixed
    {
        return $visitor->visitWhileLoop($this);
    }
}