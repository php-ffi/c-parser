<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Declaration;

/**
 * Declaration that a header consists of, written at the root of the file.
 * A static assertion is one as well, though it may be a member of a
 * structure or a union too.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.9.1 General
 */
interface RootDeclarationInterface extends DeclarationInterface {}
