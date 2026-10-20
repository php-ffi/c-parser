<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList\BalancedTokenSequence;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\NodeList;

/**
 * Arguments of an attribute, split at the commas outside of nested brackets.
 * An argument is an expression or a {@see BalancedTokenSequence}.
 *
 * @template-extends NodeList<ExpressionInterface|BalancedTokenSequence>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13.2 General
 */
final class ArgumentList extends NodeList {}
