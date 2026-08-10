<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\RecordType;

use FFI\Parser\Node\Type\RecordType;

/**
 * Union type, `union`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 */
final class UnionType extends RecordType {}
