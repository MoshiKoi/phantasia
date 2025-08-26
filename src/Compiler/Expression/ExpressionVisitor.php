<?php

namespace Phantasia\Compiler\Expression;

/**
 * @template T
 */
interface ExpressionVisitor
{
    /**
     * @return T
     */
    public function visitNumberExpression(NumberExpression $expression): mixed;

    /**
     * @return T
     */
    public function visitStringExpression(StringExpression $expression): mixed;

    /**
     * @return T
     */
    public function visitBooleanLiteral(BooleanLiteral $boolean): mixed;

    /**
     * @return T
     */
    public function visitNilLiteral(NilLiteral $expression): mixed;

    /**
     * @return T
     */
    public function visitBinaryExpression(BinaryExpression $expression): mixed;

    /**
     * @return T
     */
    public function visitAssignment(Assignment $statement): mixed;

    /**
     * @return T
     */
    public function visitFunctionCall(FunctionCall $functionCall): mixed;

    /**
     * @return T
     */
    public function visitSubscript(SubscriptExpression $subscript): mixed;

    /**
     * @return T
     */
    public function visitVariable(Variable $variable): mixed;
}