<?php

namespace Phantasia\Compiler;

use Phantasia\Compiler\Expression\{
    Expression,
    NumberExpression,
    StringExpression,
    BooleanLiteral,
    NilLiteral,
    BinaryExpression,
    Assignment,
    FunctionCall,
    SubscriptExpression,
    Variable,
    ExpressionVisitor,
};
use Phantasia\Compiler\Statement\{
    Statement,
    DeclarationStatement,
    FunctionDeclaration,
    ExpressionStatement,
    ReturnStatement,
    IfStatement,
    WhileLoop,
    StatementVisitor,
};

use Symfony\Component\Console\Helper\TreeNode;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @implements StatementVisitor<TreeNode>
 * @implements ExpressionVisitor<TreeNode>
 */
class AstToTreeNodeConverter implements StatementVisitor, ExpressionVisitor
{
    public function __construct(private SymfonyStyle $output)
    {
    }

    public function visitDeclarationStatement(DeclarationStatement $statement): TreeNode
    {
        return new TreeNode('AssignmentStatement', [
            $statement->variable->accept($this),
            $statement->expression->accept($this),
        ]);
    }

    public function visitFunctionDeclaration(FunctionDeclaration $declaration): TreeNode
    {
        return new TreeNode('FunctionDeclaration', [
            $declaration->name->accept($this),
            new TreeNode('Parameters', array_map(fn(Variable $v) => $v->accept($this), $declaration->parameters)),
            new TreeNode("Body", array_map(fn(Statement $s) => $s->accept($this), $declaration->body)),
        ]);
    }

    public function visitExpressionStatement(ExpressionStatement $statement): TreeNode
    {
        return new TreeNode('ExpressionStatement', [$statement->expression->accept($this)]);
    }

    public function visitReturn(ReturnStatement $statement): TreeNode
    {
        return new TreeNode("ReturnStatement", [$statement->expression->accept($this)]);
    }

    public function visitIfStatement(IfStatement $statement): TreeNode
    {
        return new TreeNode("IfStatement", [
            $statement->condition->accept($this),
            new TreeNode(
                'IfBody',
                array_map(fn(Statement $s) => $s->accept($this), $statement->ifBody)
            ),
            new TreeNode(
                'ElseBody',
                array_map(fn(Statement $s) => $s->accept($this), $statement->elseBody)
            ),
        ]);
    }

    public function visitWhileLoop(WhileLoop $statement): TreeNode
    {
        return new TreeNode("WhileLoop", [
            $statement->condition->accept($this),
            new TreeNode(
                "Body",
                array_map(fn(Statement $s) => $s->accept($this), $statement->body)
            ),
        ]);
    }

    public function visitNumberExpression(NumberExpression $expression): TreeNode
    {
        return new TreeNode("Number($expression->value)");
    }

    public function visitStringExpression(StringExpression $expression): TreeNode
    {
        return new TreeNode("String(<string>\"$expression->value\"</string>)");
    }

    public function visitBooleanLiteral(BooleanLiteral $boolean): TreeNode
    {
        return new TreeNode($boolean->value ? 'Boolean(<boolean>true</boolean>)' : 'Boolean(<boolean>false</boolean>)');
    }

    public function visitNilLiteral(NilLiteral $expression): TreeNode
    {
        return new TreeNode('<nil>nil</nil>');
    }

    public function visitBinaryExpression(BinaryExpression $expression): TreeNode
    {
        return new TreeNode($expression->operator->name, [
            $expression->lhs->accept($this),
            $expression->rhs->accept($this),
        ]);
    }


    public function visitAssignment(Assignment $assignment): TreeNode
    {
        return new TreeNode('AssignmentStatement', [
            $assignment->variable->accept($this),
            $assignment->expression->accept($this),
        ]);
    }

    public function visitFunctionCall(FunctionCall $functionCall): TreeNode
    {
        return new TreeNode('FunctionCall', [
            $functionCall->function->accept($this),
            new TreeNode(
                'Parameters',
                array_map(fn(Expression $p) => $p->accept($this), $functionCall->parameters)
            ),
        ]);
    }

    public function visitSubscript(SubscriptExpression $subscript): TreeNode
    {
        return new TreeNode('Subscript', [
            $subscript->expression->accept($this),
            $subscript->subscript->accept($this),
        ]);
    }

    public function visitVariable(Variable $variable): TreeNode
    {
        return new TreeNode("Variable(<fg=blue>$variable->name</>)");
    }
}