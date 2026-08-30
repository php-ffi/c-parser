<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\CallExpression;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\NodeList;

/**
 * Arguments of a function call, the list between its parentheses. An empty
 * list starts where the parenthesis does.
 *
 * @template-extends NodeList<ExpressionInterface>
 */
final class ArgumentList extends NodeList {}
