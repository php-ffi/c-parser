<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\MemberExpression;

use FFI\Parser\Node\Expression\MemberExpression;

/**
 * Access to a member of a structure or a union through a pointer to it:
 * `pointer->member`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.3.4 Structure and union members
 */
final class PointerMemberExpression extends MemberExpression {}
