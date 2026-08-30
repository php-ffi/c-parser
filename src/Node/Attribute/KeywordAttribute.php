<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

/**
 * Attribute spelled by a keyword, which is its name: the calling
 * conventions of MSVC (`__cdecl`, `__stdcall`, `__fastcall`, `__thiscall`,
 * `__vectorcall`), its modifiers of pointers (`__ptr32`, `__ptr64`,
 * `__based(base)`), the nullability of Clang (`_Nonnull`, `_Nullable`,
 * `_Nullable_result`, `_Null_unspecified`) and its pointer authentication
 * (`__ptrauth(key, address, discriminator)`). Only `__based` and `__ptrauth`
 * have an argument clause. A calling convention is kept where it is
 * written, such as among the declaration specifiers in
 * `void __stdcall f(void);`, the function type it applies to is up to the
 * consumer.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/argument-passing-and-naming-conventions MSVC, Calling conventions
 * @link https://learn.microsoft.com/en-us/cpp/cpp/based-pointers-cpp MSVC, Based pointers
 * @link https://clang.llvm.org/docs/AttributeReference.html#nullability-attributes Clang, Nullability attributes
 * @link https://clang.llvm.org/docs/PointerAuthentication.html Clang, Pointer authentication
 */
final class KeywordAttribute extends Attribute {}
