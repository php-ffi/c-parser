<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier\FunctionSpecifier;

use FFI\Parser\Node\Specifier\FunctionSpecifier;

/**
 * MSVC: the `__forceinline` function specifier.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/inline-functions-cpp MSVC, inline, __inline, __forceinline
 */
final class ForceInlineSpecifier extends FunctionSpecifier {}
