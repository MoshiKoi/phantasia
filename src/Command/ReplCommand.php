<?php

namespace Phantasia\Command;

use Phantasia\Compiler\{
    AstToTreeNodeConverter,
    Interpreter,
    Parser,
    SyntaxException
};
use Phantasia\Compiler\Statement\ExpressionStatement;
use Phantasia\Compiler\Tokenization\Tokenizer;
use Phantasia\Runtime\{
    Nil,
    Number,
    Boolean,
    PhantasiaString,
    PhantasiaFunction,
    NativeFunction
};

use Symfony\Component\Console\Attribute\{AsCommand, Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\{TreeHelper, TreeNode, TreeStyle};
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'repl', description: 'Start a REPL')]
class ReplCommand extends Command
{
    public function __invoke(
        SymfonyStyle $io,
        #[Option('Show AST')] bool $show_ast = false
    ) {
        $io->getFormatter()->setStyle('string', new OutputFormatterStyle('green'));
        $io->getFormatter()->setStyle('nil', new OutputFormatterStyle('red'));
        $io->getFormatter()->setStyle('boolean', new OutputFormatterStyle('red'));
        $io->getFormatter()->setStyle('number', new OutputFormatterStyle('cyan'));
        $io->getFormatter()->setStyle('function', new OutputFormatterStyle('magenta'));

        $interpreter = new Interpreter($io);

        $io->title('REPL started');

        while (true) {
            $io->write('<fg=bright-blue>>></> ');
            $input = readline();

            if ($input === false) {
                break;
            }

            $tokenizer = new Tokenizer($input);
            $parser = new Parser($tokenizer);

            try {
                $ast = $parser->parse();
            } catch (SyntaxException $ex) {
                $io->error("Syntax error: " . $ex->getMessage());
                continue;
            }

            if ($show_ast) {
                $io->section('Parsed AST:');

                $ast_printer = new AstToTreeNodeConverter($io);
                $tree = TreeHelper::createTree(
                    $io,
                    new TreeNode('Program', array_map(fn($node) => $node->accept($ast_printer), $ast)),
                    [],
                    TreeStyle::rounded()
                );
                $tree->render();
                $io->section("Interpreting:");
            }

            foreach ($ast as $statement) {
                if ($statement instanceof ExpressionStatement) {
                    $value = $statement->expression->accept($interpreter);
                    match (true) {
                        $value instanceof Nil => $io->writeln('<nil>nil</nil>'),
                        $value instanceof PhantasiaString => $io->writeln("<string>\"$value->value\"</string>"),
                        $value instanceof Number => $io->writeln("<number>$value->value</number>"),
                        $value instanceof PhantasiaFunction => $io->writeln('<function>function</function>'),
                        $value instanceof NativeFunction => $io->writeln('<function>native function</function>'),
                        $value instanceof Boolean => $io->writeln($value->value ? "<boolean>true</boolean>" : "<boolean>false</boolean>"),
                    };
                } else {
                    $statement->accept($interpreter);
                }
            }
        }
    }
}