<?php declare(strict_types=1);

use Phantasia\Compiler\Tokenization\Tokenizer;
use Phantasia\Compiler\Parser;
use Phantasia\Compiler\Interpreter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

final class PhantasiaTest extends TestCase
{
    public function testEmptySourceIsValid(): void
    {
        $source = '';
        $output = new BufferedOutput();
        $tokenizer = new Tokenizer($source);
        $parser = new Parser($tokenizer);
        $ast = $parser->parse();
        $interpreter = new Interpreter($output);
        foreach ($ast as $statement) {
            $statement->accept($interpreter);
        }
        $this->assertEquals('', $output->fetch());
    }

    public function testAddition(): void
    {
        $source = 'print(stringify(1 + 2))';
        $output = new BufferedOutput();
        $tokenizer = new Tokenizer($source);
        $parser = new Parser($tokenizer);
        $ast = $parser->parse();
        $interpreter = new Interpreter($output);
        foreach ($ast as $statement) {
            $statement->accept($interpreter);
        }
        $this->assertEquals("3" . PHP_EOL, $output->fetch());
    }
}
