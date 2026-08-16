<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

/**
 * Attribute of the MSVC syntax, `__declspec(dllimport)`. The attributes of
 * one `__declspec` are separated by spaces, `__declspec(noreturn dllimport)`.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/declspec MSVC, __declspec
 */
final class DeclSpecAttribute extends Attribute {}
