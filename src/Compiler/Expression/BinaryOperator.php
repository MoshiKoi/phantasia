<?php

namespace Phantasia\Compiler\Expression;

enum BinaryOperator
{
    case Plus;
    case Minus;
    case Multiply;
    case Divide;
    case Modulo;
    case Less;
    case Greater;
    case LessEqual;
    case GreaterEqual;
    case Equal;
    case NotEqual;
}