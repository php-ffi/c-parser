<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Attribute\AttributeList;

/**
 * Clang: block pointer type, written by the `^` of a declarator, `void (^callback)(int)`.
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
         * The function type of the block, {@see null} if the specifiers give no type
         */
        public ?TypeInterface $type,
        public ?AttributeList $attributes = null,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
