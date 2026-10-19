<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Expression\AssignmentExpression;

use FFI\Parser\Node\Expression\AssignmentExpression;

/**
 * Simple assignment: `a = b`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.5.17.2 Simple assignment
 */
final class AssignExpression extends AssignmentExpression {}
