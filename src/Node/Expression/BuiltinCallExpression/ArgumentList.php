<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\BuiltinCallExpression;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\NodeList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Arguments of a built-in function, the list between its parentheses. An
 * argument is an expression, or a type name where it cannot be one: in
 * `__builtin_offsetof(struct S, a.b[2])` the first argument is a type and
 * the second one is an expression, a lone identifier is an expression. An
 * empty list starts where the parenthesis does.
 *
 * @template-extends NodeList<ExpressionInterface|TypeInterface>
 */
final class ArgumentList extends NodeList {}
