<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList\BalancedTokenSequence;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\NodeList;

/**
 * Arguments of an attribute, the list between the parentheses of
 * `aligned(8)` or `format(printf, 1, 2)`, split at the commas outside of
 * nested brackets. An argument that is a constant expression gives its
 * node, any other balanced sequence of tokens its text: `introduced=10.4` in
 * `availability(macos, introduced=10.4)`. An empty list starts where the
 * parenthesis does.
 *
 * @template-extends NodeList<ExpressionInterface|BalancedTokenSequence>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13.2 General
 */
final class ArgumentList extends NodeList {}
