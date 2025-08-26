<?php

namespace Phantasia\Compiler\Statement;

use Phantasia\Compiler\Expression\Expression;

class IfStatement extends Statement
{
    /**
     * @param Expression $condition
     * @param Statement[] $ifBody
     * @param Statement[] $elseBody
     */
    public function __construct(
        public Expression $condition,
        public array $ifBody,
        public array $elseBody
    ) {
    }

    public function accept(StatementVisitor $visitor): mixed
    {
        return $visitor->visitIfStatement($this);
    }
}