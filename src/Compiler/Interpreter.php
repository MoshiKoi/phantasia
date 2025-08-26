<?php

namespace Phantasia\Compiler;

use Exception;
use Phantasia\Compiler\Expression\{
    Expression,
    NumberExpression,
    StringExpression,
    NilLiteral,
    BinaryExpression,
    Assignment,
    BinaryOperator,
    FunctionCall,
    SubscriptExpression,
    Variable,
    ExpressionVisitor
};
use Phantasia\Compiler\Expression\BooleanLiteral;
use Phantasia\Compiler\Statement\{
    DeclarationStatement,
    FunctionDeclaration,
    ExpressionStatement,
    ReturnStatement,
    IfStatement,
    WhileLoop,
    StatementVisitor
};
use Phantasia\Runtime\{
    Value,
    Number,
    PhantasiaString,
    Boolean,
    Nil,
    NativeFunction,
    PhantasiaFunction,
};

use Symfony\Component\Console\Output\OutputInterface;

class Scope
{
    /**
     * @var array<string, Value>
     */
    private array $variables = [];

    public ?Value $returnValue = null;

    public function __construct(public ?Scope $parent = null)
    {
    }

    public function getVariable(string $name): ?Value
    {
        return $this->variables[$name] ?? $this->parent?->getVariable($name);
    }

    public function declareVariable(string $name, Value $value): void
    {
        $this->variables[$name] = $value;
    }

    public function setVariable(string $name, Value $value): void
    {
        if (isset($this->variables[$name])) {
            $this->variables[$name] = $value;
        } else if ($this->parent !== null) {
            $this->parent->setVariable($name, $value);
        } else {
            throw new Exception("Undefined variable: $name");
        }
    }
}

function stringify(Value $value): PhantasiaString
{
    return match (true) {
        $value instanceof PhantasiaString => $value,
        $value instanceof Number => new PhantasiaString(strval($value->value)),
        $value instanceof Boolean => new PhantasiaString($value->value ? 'true' : 'false'),
        $value instanceof PhantasiaFunction => new PhantasiaString('function'),
        $value instanceof NativeFunction => new PhantasiaString('native function'),
        $value instanceof Nil => new PhantasiaString('nil'),
    };
}

/**
 * @implements StatementVisitor<null>
 * @implements ExpressionVisitor<Value>
 */
class Interpreter implements StatementVisitor, ExpressionVisitor
{
    private Scope $scope;

    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->scope = new Scope();
        $this->scope->declareVariable("print", new NativeFunction(function (PhantasiaString $message) {
            $this->output->writeln($message->value);
            return Nil::getInstance();
        }));
        $this->scope->declareVariable("stringify", new NativeFunction(fn(Value $value) => stringify($value)));
    }

    public function visitDeclarationStatement(DeclarationStatement $statement): null
    {
        $this->scope->declareVariable(
            $statement->variable->name,
            $statement->expression->accept($this)
        );
        return null;
    }

    public function visitFunctionDeclaration(FunctionDeclaration $declaration): null
    {
        $this->scope->declareVariable(
            $declaration->name->name,
            new PhantasiaFunction($declaration->parameters, $declaration->body)
        );
        return null;
    }

    public function visitExpressionStatement(ExpressionStatement $statement): null
    {
        $statement->expression->accept($this);
        return null;
    }

    public function visitReturn(ReturnStatement $statement): null
    {
        $this->scope->returnValue = $statement->expression->accept($this);
        return null;
    }

    public function visitIfStatement(IfStatement $statement): null
    {
        $condition = $statement->condition->accept($this);

        if (!($condition instanceof Boolean)) {
            throw new Exception("Condition must be a boolean value: " . stringify($condition)->value);
        }


        $body = $condition->value ? $statement->ifBody : $statement->elseBody;
        foreach ($body as $s) {
            $s->accept($this);
            if ($this->scope->returnValue !== null) {
                break;
            }
        }
        return null;
    }

    public function visitWhileLoop(WhileLoop $loop): null
    {
        while (true) {
            $condition = $loop->condition->accept($this);

            if (!($condition instanceof Boolean)) {
                throw new Exception("Condition must be a boolean value: " . stringify($condition)->value);
            }

            if (!$condition->value) {
                return null;
            }

            foreach ($loop->body as $s) {
                $s->accept($this);
                if ($this->scope->returnValue !== null) {
                    return null;
                }
            }
        }
    }

    public function visitNumberExpression(NumberExpression $expression): Value
    {
        return new Number($expression->value);
    }

    public function visitStringExpression(StringExpression $expression): Value
    {
        return new PhantasiaString($expression->value);
    }

    public function visitBooleanLiteral(BooleanLiteral $boolean): Value
    {
        return Boolean::from($boolean->value);
    }

    public function visitNilLiteral(NilLiteral $expression): Value
    {
        return Nil::getInstance();
    }

    public function visitBinaryExpression(BinaryExpression $expression): Value
    {
        $dunder_method = match ($expression->operator) {
            BinaryOperator::Plus => new PhantasiaString('__add'),
            BinaryOperator::Minus => new PhantasiaString('__sub'),
            BinaryOperator::Multiply => new PhantasiaString('__mul'),
            BinaryOperator::Divide => new PhantasiaString('__div'),
            BinaryOperator::Modulo => new PhantasiaString('__mod'),
            BinaryOperator::Less => new PhantasiaString('__lt'),
            BinaryOperator::Greater => new PhantasiaString('__gt'),
            BinaryOperator::LessEqual => new PhantasiaString('__le'),
            BinaryOperator::GreaterEqual => new PhantasiaString('__ge'),
            BinaryOperator::Equal => new PhantasiaString('__eq'),
            BinaryOperator::NotEqual => new PhantasiaString('__ne'),
        };

        $lhs = $expression->lhs->accept($this);
        $function = $lhs->getProperty($dunder_method);

        if ($function === null) {
            throw new Exception("No method $dunder_method->value found on object");
        }
        return $this->call($function, [$lhs, $expression->rhs->accept($this)]);
    }

    public function visitAssignment(Assignment $assignment): Value
    {
        $rhs = $assignment->expression->accept($this);
        $this->scope->setVariable($assignment->variable->name, $rhs);
        return $rhs;
    }

    public function visitFunctionCall(FunctionCall $functionCall): Value
    {
        $function = $functionCall->function->accept($this);
        $parameters = array_map(fn(Expression $p) => $p->accept($this), $functionCall->parameters);
        return $this->call($function, $parameters);
    }

    public function visitSubscript(SubscriptExpression $subscript): Value
    {
        return $subscript->expression->accept($this)
            ->getProperty($subscript->subscript->accept($this));
    }

    public function visitVariable(Variable $variable): mixed
    {
        return $this->scope->getVariable($variable->name) ?? throw new Exception("Variable '$variable->name' not found");
    }


    /**
     * @param Value[] $parameters
     */
    public function call(Value $callable, array $parameters): Value
    {
        switch (true) {
            case $callable instanceof PhantasiaFunction:
                return $this->callPhantasiaFunction($callable, $parameters);
            case $callable instanceof NativeFunction:
                $match = array_map(
                    fn(string $class, Value $v) => is_a($v, $class),
                    $callable->getSignature(),
                    $parameters
                );

                if (!array_all($match, fn(bool $v) => $v)) {
                    throw new Exception("Invalid parameters passed: Expected "
                        . implode(', ', $callable->getSignature())
                        . ' got '
                        . implode(', ', array_map(fn($p) => $p::class, $parameters)));
                }
                return ($callable->callable)(...$parameters);
            default:
                throw new Exception("Can't call non-callable value" . $callable::class);
        }
    }

    /**
     * @param PhantasiaFunction $function
     * @param Value[] $parameters
     */
    private function callPhantasiaFunction(PhantasiaFunction $function, array $parameters): Value
    {
        $this->scope = new Scope($this->scope);

        foreach (array_map(null, $function->parameters, $parameters) as $zip) {
            $this->scope->declareVariable($zip[0]->name, $zip[1]);
        }

        foreach ($function->body as $statement) {
            $statement->accept($this);
            if ($this->scope->returnValue !== null) {
                break;
            }
        }
        $returnValue = $this->scope->returnValue ?? Nil::getInstance();
        $this->scope = $this->scope->parent;
        return $returnValue;
    }
}