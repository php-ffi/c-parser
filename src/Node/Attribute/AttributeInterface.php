<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;

/**
 * Node that is an attribute.
 *
 * @property Identifier $name
 * @property ArgumentList|null $arguments
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13 Attributes
 */
interface AttributeInterface extends NodeInterface {}
