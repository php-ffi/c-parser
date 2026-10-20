<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

/**
 * Attribute spelled by a keyword that is its name: MSVC calling conventions and pointer modifiers
 * (`__cdecl`, `__ptr32`, `__based(p)`) and Clang nullability and pointer signing
 * (`_Nonnull`, `__ptrauth(1, 0, 42)`).
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/argument-passing-and-naming-conventions MSVC, Calling conventions
 * @link https://learn.microsoft.com/en-us/cpp/cpp/ptr32-ptr64 MSVC, __ptr32, __ptr64
 * @link https://learn.microsoft.com/en-us/cpp/cpp/based-pointers-cpp MSVC, Based pointers
 * @link https://clang.llvm.org/docs/AttributeReference.html#nullability-attributes Clang, Nullability attributes
 * @link https://clang.llvm.org/docs/PointerAuthentication.html Clang, Pointer Authentication
 */
final class KeywordAttribute extends Attribute {}
