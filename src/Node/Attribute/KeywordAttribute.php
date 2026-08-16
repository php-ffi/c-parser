<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

use FFI\Parser\Node\Identifier;

/**
 * Attribute spelled by a keyword alone, which is its name: the calling
 * conventions of MSVC (`__cdecl`, `__stdcall`, `__fastcall`, `__thiscall`,
 * `__vectorcall`), its pointer sizes (`__ptr32`, `__ptr64`) and the
 * nullability of Clang (`_Nonnull`, `_Nullable`, `_Nullable_result`,
 * `_Null_unspecified`). It has no argument clause. A calling convention is
 * kept where it is written, such as among the declaration specifiers in
 * `void __stdcall f(void);`, the function type it applies to is up to the
 * consumer.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/argument-passing-and-naming-conventions MSVC, Calling conventions
 * @link https://clang.llvm.org/docs/AttributeReference.html#nullability-attributes Clang, Nullability attributes
 */
final class KeywordAttribute extends Attribute
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(Identifier $name, int $offset = 0)
    {
        parent::__construct($name, null, $offset);
    }
}
