<?php

namespace Phantasia\Compiler;

use Exception;

class SyntaxException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}