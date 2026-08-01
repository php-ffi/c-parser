<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Base of the failures caused by the parsed source itself, i.e. the ones
 * that point to a particular place in it.
 */
abstract class ParseRuntimeException extends ParserException
{
    final public function __construct(
        string $message,
        /**
         * The source the failure occurred in
         */
        public readonly ReadableInterface $source,
        /**
         * Offset of the first byte of the failed fragment in the source
         *
         * @var int<0, max>
         */
        public readonly int $offset,
        /**
         * Size of the failed fragment in bytes or {@see null} in case of it
         * is not known
         *
         * @var int<0, max>|null
         */
        public readonly ?int $length = null,
    ) {
        parent::__construct($message);
    }
}
