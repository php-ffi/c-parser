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
 * Base of the failures that point to a particular place in the parsed source.
 */
abstract class ParseRuntimeException extends ParserException
{
    protected function __construct(
        string $message,
        public readonly ReadableInterface $source,
        /**
         * @var int<0, max>
         */
        public readonly int $offset,
        /**
         * Size of the failed fragment in bytes, {@see null} if unknown
         *
         * @var int<0, max>|null
         */
        public readonly ?int $length = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Prints the failure with the snippet of the source, or as any other
     * exception if the source cannot be read again.
     */
    public function __toString(): string
    {
        try {
            $contents = $this->source->content;
            // @phpstan-ignore-next-line the contract of the property tells it throws in its text only
        } catch (SourceExceptionInterface) {
            return parent::__toString();
        }

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
