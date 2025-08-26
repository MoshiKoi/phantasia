<?php

namespace Phantasia\Runtime;

class Nil extends Value
{
    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        static $instance = new self;
        return $instance;
    }

    public function getProperty(Value $name): Value
    {
        return self::getInstance();
    }
}