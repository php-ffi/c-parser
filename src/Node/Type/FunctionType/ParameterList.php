<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\FunctionType;

use FFI\Parser\Node\NodeList;

/**
 * Parameters of a function type without the trailing `...`.
 *
 * @template-extends NodeList<Parameter>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.4 Function declarators
 */
final class ParameterList extends NodeList {}
