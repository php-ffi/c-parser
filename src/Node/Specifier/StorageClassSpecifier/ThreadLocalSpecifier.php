<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier\StorageClassSpecifier;

use FFI\Parser\Node\Specifier\StorageClassSpecifier;

/**
 * The `thread_local` storage-class specifier, also spelled `_Thread_local`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.2 Storage-class specifiers
 */
final class ThreadLocalSpecifier extends StorageClassSpecifier {}
