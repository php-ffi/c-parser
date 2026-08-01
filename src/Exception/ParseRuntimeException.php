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
    /**
     * @param ReadableInterface $source the source the failure occurred in
     * @param int<0, max> $offset offset of the first byte of the failed
     *        fragment in the source
     * @param int<0, max>|null $length size of the failed fragment in bytes or
     *        {@see null} in case of it is not known
     */
    final public function __construct(
        string $message,
        public readonly ReadableInterface $source,
        public readonly int $offset,
        public readonly ?int $length = null,
    ) {
        parent::__construct($message);
    }
}
