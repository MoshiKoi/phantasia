<?php

namespace Phantasia\Runtime;

use Phantasia\Compiler\Expression\Variable;
use Phantasia\Compiler\Statement\Statement;

class PhantasiaFunction extends Value
{
    /**
     * @param Variable[] $parameters
     * @param Statement[] $body
     */
    public function __construct(public array $parameters, public array $body)
    {
    }

    public function getProperty(Value $name): Value
    {
        return Nil::getInstance();
    }
}