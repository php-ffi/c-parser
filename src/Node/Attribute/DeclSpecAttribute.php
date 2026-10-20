<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Attribute;

/**
 * MSVC attribute, `__declspec(dllimport)`.
 *
 * @link https://learn.microsoft.com/en-us/cpp/cpp/declspec MSVC, __declspec
 */
final class DeclSpecAttribute extends Attribute {}
