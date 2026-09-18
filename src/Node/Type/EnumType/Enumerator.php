<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\EnumType;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Declaration\Declaration;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Identifier;

/**
 * Constant of an enumeration, such as `A` or `B = 2`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.3 Enumeration specifiers
 */
final class Enumerator extends Declaration
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the constant
         */
        public Identifier $name,
        /**
         * The constant expression of the value, {@see null} in case of it
         * is not written, i.e. the value follows the previous one
         */
        public ?ExpressionInterface $value = null,
        /**
         * The attributes after the name, `A [[deprecated]] = 1`,
         * {@see null} in case of none is written
         */
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
