<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression;

use FFI\Parser\Node\Initializer\InitializerList;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Unnamed object of a type initialized by a list, `(int[]){1, 2}`. The
 * storage-class specifiers C23 allows in the parentheses (`(static int){1}`)
 * are kept by a {@see \FFI\Parser\Node\Type\SpecifiedType} as in any type
 * name.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.6 Compound literals
 */
final class CompoundLiteralExpression extends Expression
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The type of the object
         */
        public TypeInterface $type,
        /**
         * The initializer in braces
         */
        public InitializerList $initializer,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
