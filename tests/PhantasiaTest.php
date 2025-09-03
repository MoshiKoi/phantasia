<?php declare(strict_types=1);

use Phantasia\Compiler\Tokenization\Tokenizer;
use Phantasia\Compiler\Parser;
use Phantasia\Compiler\Interpreter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

function runPhantasia(string $source): string
{
    $output = new BufferedOutput();
    $tokenizer = new Tokenizer($source);
    $parser = new Parser($tokenizer);
    $ast = $parser->parse();
    $interpreter = new Interpreter($output);
    foreach ($ast as $statement) {
        $statement->accept($interpreter);
    }
    return $output->fetch();
}

final class PhantasiaTest extends TestCase
{
    public function testEmptySourceIsValid(): void
    {
        $this->assertEquals('', runPhantasia(''));
    }

    public function testAddition(): void
    {
        $this->assertEquals("3" . PHP_EOL, runPhantasia('print(stringify(1 + 2))'));
    }

    public function testComment(): void
    {
        $this->assertEquals('', runPhantasia('# This is a comment'));
    }
}
