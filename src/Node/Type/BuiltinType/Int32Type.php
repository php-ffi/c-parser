<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\BuiltinType;

use FFI\Parser\Node\Type\BuiltinType;

/**
 * The `__int32` type, a 32-bit integer type of MSVC, the size of `int`.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/int8-int16-int32-int64 MSVC, __int8, __int16, __int32, __int64
 */
final class Int32Type extends BuiltinType {}
