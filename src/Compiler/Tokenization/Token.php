<?php

namespace Phantasia\Compiler\Tokenization;

final class Token
{
    public function __construct(
        public TokenType $type,
        public string|float|null $value = null
    ) {
    }
}