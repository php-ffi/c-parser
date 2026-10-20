<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\EnumType;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\Declaration;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Identifier;

/**
 * Constant of an enumeration, `A` or `B = 2`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.3 Enumeration specifiers
 */
final class Enumerator extends Declaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        public Identifier $name,
        /**
         * The constant expression, {@see null} if not written
         */
        public ?ExpressionInterface $value = null,
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
