<?php

namespace Phantasia\Compiler\Statement;

/**
 * @template T
 */
interface StatementVisitor
{
    /**
     * @return T
     */
    public function visitDeclarationStatement(DeclarationStatement $statement): mixed;

    /**
     * @return T
     */
    public function visitFunctionDeclaration(FunctionDeclaration $declaration): mixed;

    /**
     * @return T
     */
    public function visitExpressionStatement(
        ExpressionStatement $statement
    ): mixed;

    /**
     * @return T
     */
    public function visitReturn(ReturnStatement $statement): mixed;

    /**
     * @return T
     */
    public function visitIfStatement(IfStatement $statement): mixed;

    /**
     * @return T
     */
    public function visitWhileLoop(WhileLoop $loop): mixed;
}