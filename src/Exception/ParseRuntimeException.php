<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;

/**
 * Base of the failures caused by the parsed source itself, i.e. the ones
 * that point to a particular place in it.
 */
abstract class ParseRuntimeException extends ParserException
{
    final protected function __construct(
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

    /**
     * Prints the failure along with the snippet of the source that holds it,
     * or as any other exception in case of the source cannot be read again.
     */
    public function __toString(): string
    {
        try {
            $contents = $this->source->content;
            // @phpstan-ignore-next-line the contract of the property tells it throws in its text only
        } catch (SourceExceptionInterface) {
            // A source that cannot be read again, such as a removed file
            return parent::__toString();
        }

        // The snippet of the contents read above, with the name of the file
        $snippet = $this->source instanceof FileInterface
            ? VirtualSource::createFromString($this->source->pathname, $contents)
            : new StringSource($contents);

        return (new ErrorPrinter())
            ->print($this)
            ->withSource($snippet)
            ->withMessage($this->message)
            ->withClass(static::class)
            ->withInterval($this->offset, (int) $this->length)
            ->render();
    }
}
