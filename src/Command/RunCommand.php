<?php

namespace Phantasia\Command;

use Phantasia\Compiler\SyntaxException;
use Phantasia\Compiler\Tokenization\Tokenizer;
use Phantasia\Compiler\{
    AstToTreeNodeConverter,
    Interpreter,
    Parser
};

use Symfony\Component\Console\Attribute\{Argument, AsCommand, Option};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\{TreeHelper, TreeNode, TreeStyle};
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'run', description: "Run a Phantasia program")]
class RunCommand extends Command
{
    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Phantasia file')] string $filename,
        #[Option('Show AST')] bool $show_ast = false,
    ): int {
        $io->getFormatter()->setStyle('string', new OutputFormatterStyle('green'));
        $io->getFormatter()->setStyle('nil', new OutputFormatterStyle('red'));
        $io->getFormatter()->setStyle('boolean', new OutputFormatterStyle('red'));
        $io->getFormatter()->setStyle('number', new OutputFormatterStyle('cyan'));
        $io->getFormatter()->setStyle('function', new OutputFormatterStyle('magenta'));

        $filesystem = new Filesystem();
        $source = $filesystem->readFile($filename);
        $tokenizer = new Tokenizer($source);
        $parser = new Parser($tokenizer);

        try {
            $ast = $parser->parse();
        } catch (SyntaxException $ex) {
            $io->error("Syntax error: " . $ex->getMessage());
            return Command::FAILURE;
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

        $interpreter = new Interpreter($io);

        foreach ($ast as $statement) {
            $statement->accept($interpreter);
        }

        return Command::SUCCESS;
    }
}