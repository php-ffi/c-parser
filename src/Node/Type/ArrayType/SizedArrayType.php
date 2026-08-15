<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\ArrayType;

use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Type\ArrayType;
use FFI\Parser\Node\Type\QualifiedType\TypeQualifier;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Array with a size, `int a[3]` or `int a[static 3]` in a parameter. Whether
 * the size is a constant or the array has a variable length is up to its
 * value, the parser does not tell them apart.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.3 Array declarators
 */
final class SizedArrayType extends ArrayType
{
    /**
     * @param list<TypeQualifier> $qualifiers
     * @param int<0, max> $offset
     */
    public function __construct(
        ?TypeInterface $type,
        /**
         * The expression of the size
         */
        public readonly ExpressionInterface $size,
        /**
         * Whether `static` is written in the brackets: the parameter points
         * to at least the given number of elements. C allows it only in the
         * outermost array of a parameter, but the parser accepts it
         * everywhere and keeps it for a validator.
         */
        public readonly bool $isStatic = false,
        array $qualifiers = [],
        int $offset = 0,
    ) {
        parent::__construct($type, $qualifiers, $offset);
    }
}
