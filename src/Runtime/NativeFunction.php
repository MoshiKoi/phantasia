<?php

namespace Phantasia\Runtime;

use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

class NativeFunction extends Value
{
    /**
     * @var string[]
     */
    private array $signature;

    /**
     * @param callable<Value[],Value>
     */
    public function __construct(public $callable)
    {
        $reflect = new ReflectionFunction($callable);
        $getType = function (ReflectionParameter $parameter) {
            $type = $parameter->getType();
            assert($type instanceof ReflectionNamedType);
            return $type->getName();
        };
        $this->signature = array_map($getType, $reflect->getParameters());
    }

    public function getProperty(Value $name): Value
    {
        return Nil::getInstance();
    }

    /**
     * @return string[]
     */
    public function getSignature(): array
    {
        return $this->signature;
    }
}