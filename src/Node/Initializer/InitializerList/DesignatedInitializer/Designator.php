<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer;

use FFI\Parser\Node\Node;

/**
 * Base of the designators, the parts of the name of an initialized
 * subobject. The kind of the designator is the class of the node.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 */
abstract class Designator extends Node {}
