<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\ModifiedType;

/**
 * Type specifier that modifies the base of the type rather than naming it.
 * The value of a case is the keyword.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 */
enum TypeModifier: string
{
    /**
     * `signed`.
     */
    case Signed = 'signed';

    /**
     * `unsigned`.
     */
    case Unsigned = 'unsigned';

    /**
     * `short`.
     */
    case Short = 'short';

    /**
     * `long`, written twice for `long long`.
     */
    case Long = 'long';

    /**
     * `_Complex`.
     */
    case Complex = '_Complex';

    /**
     * `_Imaginary`, a type specifier of Annex G.
     */
    case Imaginary = '_Imaginary';
}
