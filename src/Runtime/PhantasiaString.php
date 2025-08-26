<?php

namespace Phantasia\Runtime;

class PhantasiaString extends Value
{
    public function __construct(public string $value)
    {
    }

    public function getProperty(Value $name): Value
    {
        if ($name instanceof PhantasiaString) {
            return match ($name->value) {
                '__add' => new NativeFunction(fn(PhantasiaString $self, PhantasiaString $other) => new PhantasiaString("$self->value$other->value")),
                default => Nil::getInstance(),
            };
        }
        return Nil::getInstance();
    }
}