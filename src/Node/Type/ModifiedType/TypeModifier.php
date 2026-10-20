<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\ModifiedType;

/**
 * Type specifier that modifies the base of the type: `long`, `unsigned`. The value of a case is the keyword.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 */
enum TypeModifier: string
{
    case Signed = 'signed';

    case Unsigned = 'unsigned';

    case Short = 'short';

    /**
     * Written twice for `long long`.
     */
    case Long = 'long';

    case Complex = '_Complex';

    /**
     * A type specifier of Annex G.
     */
    case Imaginary = '_Imaginary';
}
