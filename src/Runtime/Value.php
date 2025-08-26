<?php

namespace Phantasia\Runtime;

abstract class Value
{
    abstract public function getProperty(Value $name): Value;
}