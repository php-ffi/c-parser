<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Clang: block pointer type, written by the `^` of a declarator:
 * `void (^callback)(int)`. The qualifiers of the pointer itself go to a
 * {@see QualifiedType} around this node, as for a {@see PointerType}.
 *
 * @link https://clang.llvm.org/docs/BlockLanguageSpec.html Clang, Language Specification for Blocks
 */
final class BlockPointerType extends Type
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The function type the block has, {@see null} in case of the
         * specifiers give no type
         */
        public readonly ?TypeInterface $type,
        /**
         * The attributes written after the `^`, {@see null} in case of none
         * is written
         */
        public readonly ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
