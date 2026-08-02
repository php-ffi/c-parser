<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type;

use FFI\Parser\Node\Node;

/**
 * Base of the types written in a cast, `sizeof` and `alignof`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.8 Type names
 */
abstract class Type extends Node {}
