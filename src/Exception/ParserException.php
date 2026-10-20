<?php

declare(strict_types=1);

namespace FFI\Parser\Exception;

/**
 * Base of every exception thrown by the parser. The kind of failure is the
 * class of the exception.
 */
abstract class ParserException extends \Exception {}
