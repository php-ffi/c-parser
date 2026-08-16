<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\Attribute\Attribute\ArgumentList;
use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\NodeInterface;

/**
 * Node that is an attribute. Every {@see Attribute} is one, and a node of
 * another kind may be one as well.
 *
 * @property-read Identifier $name the name of the attribute as written,
 *        a keyword included
 * @property-read ArgumentList|null $arguments the argument clause,
 *        {@see null} in case of none is written
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.13 Attributes
 */
interface AttributeInterface extends NodeInterface {}
