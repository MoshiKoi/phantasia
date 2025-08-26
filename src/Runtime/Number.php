<?php

namespace Phantasia\Runtime;

class Number extends Value
{
    public function __construct(public float $value)
    {
    }

    public function getProperty(Value $name): Value
    {
        if ($name instanceof PhantasiaString) {
            return match ($name->value) {
                '__add' => new NativeFunction(fn(Number $self, Number $other) => new Number($self->value + $other->value)),
                '__sub' => new NativeFunction(fn(Number $self, Number $other) => new Number($self->value - $other->value)),
                '__mul' => new NativeFunction(fn(Number $self, Number $other) => new Number($self->value * $other->value)),
                '__div' => new NativeFunction(fn(Number $self, Number $other) => new Number($self->value / $other->value)),
                '__mod' => new NativeFunction(fn(Number $self, Number $other) => new Number($self->value % $other->value)),
                '__lt' => new NativeFunction(fn(Number $self, Number $other) => Boolean::from($self->value < $other->value)),
                '__gt' => new NativeFunction(fn(Number $self, Number $other) => Boolean::from($self->value > $other->value)),
                '__eq' => new NativeFunction(fn(Number $self, Number $other) => Boolean::from($self->value === $other->value)),
                '__ne' => new NativeFunction(fn(Number $self, Number $other) => Boolean::from($self->value !== $other->value)),
                '__le' => new NativeFunction(fn(Number $self, Number $other) => Boolean::from($self->value <= $other->value)),
                '__ge' => new NativeFunction(fn(Number $self, Number $other) => Boolean::from($self->value >= $other->value)),
                default => Nil::getInstance(),
            };
        }
        return Nil::getInstance();
    }
}