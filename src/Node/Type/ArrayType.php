<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;
use FFI\Parser\Node\Type\QualifiedType\TypeQualifier;

/**
 * Base of the array types, written by the `[...]` of a declarator. The kind
 * of the size is the class: an expression, none or `*`.
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
         * The element type, {@see null} in case of the specifiers give no
         * type, such as in `static (a)[3];`
         */
        public readonly ?TypeInterface $type,
        /**
         * The qualifiers written in the brackets (`int a[const 3]`), in the
         * order written. C allows them only in the outermost array of a
         * parameter, where they qualify the pointer the array is adjusted
         * to, but the parser accepts them everywhere and keeps them for a
         * validator
         *
         * @var list<TypeQualifier>
         */
        public readonly array $qualifiers = [],
        /**
         * The C23 attributes written after the brackets, `[3] [[x]]`,
         * {@see null} in case of none is written
         */
        public readonly ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
