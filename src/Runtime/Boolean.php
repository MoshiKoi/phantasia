<?php

namespace Phantasia\Runtime;

class Boolean extends Value
{
    private function __construct(public bool $value)
    {
    }

    public static function getTrue()
    {
        static $true = new Boolean(true);
        return $true;
    }

    public static function getFalse()
    {
        static $false = new Boolean(false);
        return $false;
    }

    public static function from(bool $value)
    {
        return $value ? self::getTrue() : self::getFalse();
    }

    public function getProperty(Value $name): Value
    {
        return Nil::getInstance();
    }
}