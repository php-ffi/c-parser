<?php

declare(strict_types=1);

namespace FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator;

use FFI\Parser\Node\Identifier;
use FFI\Parser\Node\Initializer\InitializerList\DesignatedInitializer\Designator;

/**
 * Designator of a member of a structure or a union, `.a`.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 */
final class MemberDesignator extends Designator
{
    /**
     * @param int<0, max> $offset
     */
    public function __construct(
        /**
         * The name of the member
         */
        public readonly Identifier $member,
        int $offset = 0,
    ) {
        parent::__construct($offset);
    }
}
