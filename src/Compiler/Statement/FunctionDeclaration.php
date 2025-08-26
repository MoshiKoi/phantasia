<?php

namespace Phantasia\Compiler\Statement;

use Phantasia\Compiler\Expression\Variable;

class FunctionDeclaration extends Statement
{
    /**
     * @param Variable $name
     * @param Variable[] $parameters
     * @param Statement[] $body
     */
    public function __construct(public Variable $name, public array $parameters, public array $body)
    {
    }

    public function accept(StatementVisitor $visitor): mixed
    {
        return $visitor->visitFunctionDeclaration($this);
    }
}