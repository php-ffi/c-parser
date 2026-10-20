<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\ArrayType;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Expression\ExpressionInterface;
use FFI\Parser\Node\Type\ArrayType;
use FFI\Parser\Node\Type\QualifiedType\TypeQualifier;
use FFI\Parser\Node\Type\TypeInterface;

/**
 * Array with a size, `int a[3]` or `int a[static 3]`.
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
        public ExpressionInterface $size,
        /**
         * Whether `static` is written in the brackets
         */
        public bool $isStatic = false,
        array $qualifiers = [],
        ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($type, $qualifiers, $attributes, $offset);
    }
}
