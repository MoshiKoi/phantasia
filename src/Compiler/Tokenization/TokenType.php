<?php

namespace Phantasia\Compiler\Tokenization;

enum TokenType
{
    case Identifier;
    case Number;
    case String;
    case KwFn;
    case KwLet;
    case KwEnd;
    case KwReturn;
    case KwIf;
    case KwElse;
    case KwElseIf;
    case KwWhile;
    case KwTrue;
    case KwFalse;
    case KwNil;
    case LParen;
    case RParen;
    case LBrack;
    case RBrack;
    case Semicolon;
    case Comma;
    case Plus;
    case Minus;
    case Asterisk;
    case Slash;
    case Modulo;
    case Less;
    case Greater;
    case LessEqual;
    case GreaterEqual;
    case Equal;
    case NotEqual;
    case Not;
    case And;
    case Or;
    case Dot;
    case Assign;
}