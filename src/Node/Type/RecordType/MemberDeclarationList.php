<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Type\RecordType;

use FFI\Parser\Node\Declaration\StaticAssertDeclaration;
use FFI\Parser\Node\NodeList;

/**
 * Members of a structure or a union, the body between the braces. C23
 * requires at least one member, but GCC allows an empty body, and so does
 * the parser. An empty list starts where the brace does.
 *
 * @template-extends NodeList<MemberDeclaration|StaticAssertDeclaration>
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 */
final class MemberDeclarationList extends NodeList {}
