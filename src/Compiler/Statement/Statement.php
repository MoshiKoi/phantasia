<?php

namespace Phantasia\Compiler\Statement;

abstract class Statement
{
    /**
     * @template T
     * @param StatementVisitor<T> $visitor
     * @return T
     */
    abstract public function accept(StatementVisitor $visitor): mixed;
}