<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\QualifiedType;

use FFI\Parser\Node\Type\AtomicType;

/**
 * Qualifier of a type. The value of a case is the keyword.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.4 Type qualifiers
 */
enum TypeQualifier: string
{
    case Const = 'const';

    case Volatile = 'volatile';

    case Restrict = 'restrict';

    /**
     * Without a parenthesis, `_Atomic(T)` is an {@see AtomicType}.
     */
    case Atomic = '_Atomic';
}
