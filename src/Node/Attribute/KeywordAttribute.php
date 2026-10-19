<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

/**
 * Attribute spelled by a keyword, which is its name: the calling
 * conventions of MSVC (`__cdecl`, `__stdcall`, `__fastcall`, `__vectorcall`,
 * `__thiscall`, `__clrcall`, `__preserve_none`) and its modifiers of
 * pointers (`__ptr32`, `__ptr64`, `__sptr`, `__uptr`, `__unaligned`,
 * `__w64`, `__based(p)` with its arguments), the nullability of Clang
 * (`_Nonnull`, `_Nullable`, `_Nullable_result`, `_Null_unspecified`) and
 * its signing of pointers (`__ptrauth(1, 0, 42)` with its arguments). A
 * calling convention is an attribute of the function type it applies to:
 * in `int __stdcall f(void);` of the function `f`, in
 * `void (__stdcall *f)(int);` of the function `f` points to. A modifier,
 * a nullability or a signing after `*` or `^` is an attribute of the
 * pointer, among the declaration specifiers it is one of them.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/argument-passing-and-naming-conventions MSVC, Calling conventions
 * @link https://learn.microsoft.com/en-us/cpp/cpp/ptr32-ptr64 MSVC, __ptr32, __ptr64
 * @link https://learn.microsoft.com/en-us/cpp/cpp/based-pointers-cpp MSVC, Based pointers
 * @link https://clang.llvm.org/docs/AttributeReference.html#nullability-attributes Clang, Nullability attributes
 * @link https://clang.llvm.org/docs/PointerAuthentication.html Clang, Pointer Authentication
 */
final class KeywordAttribute extends Attribute {}
