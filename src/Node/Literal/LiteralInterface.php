<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Literal;

use FFI\Parser\Node\Expression\ExpressionInterface;

/**
 * Expression that is a constant or a string literal written in the source.
 *
 * @property-read non-empty-string $raw the literal exactly as written
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.4 Constants
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.4.5 String literals
 */
interface LiteralInterface extends ExpressionInterface {}
