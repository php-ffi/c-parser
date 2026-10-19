<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Specifier;

use FFI\Parser\Node\Attribute\AttributeInterface;
use FFI\Parser\Node\NodeList;

/**
 * Specifiers of a declaration or a type name that are not a part of the
 * type, in the order written, repeats included, such as `static inline` in
 * `static inline int`, and the attributes written before them and after
 * the last of them, `[[deprecated]] static int [[gnu::unused]]`. They are not
 * checked: `static extern` gives a list as well.
 *
 * @template-extends NodeList<SpecifierInterface|AttributeInterface>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.1 General
 */
final class SpecifierList extends NodeList {}
