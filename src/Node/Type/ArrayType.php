<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Type\QualifiedType\TypeQualifier;

/**
 * Base of the array types, written by the `[...]` of a declarator.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.3 Array declarators
 */
abstract class ArrayType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The element type, {@see null} if the specifiers give no type
         */
        public ?TypeInterface $type,
        /**
         * Qualifiers written in the brackets, `int a[const 3]`
         *
         * @var list<TypeQualifier>
         */
        public array $qualifiers = [],
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
