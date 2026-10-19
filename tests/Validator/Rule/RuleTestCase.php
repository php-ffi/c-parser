<?php

declare(strict_types=1);

namespace FFI\Parser\Tests\Validator\Rule;

use FFI\Parser\Tests\TestCase;
use FFI\Parser\Validator;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

abstract class RuleTestCase extends TestCase
{
    abstract protected static function createRule(): ValidatorRuleInterface;

    /**
     * @return list<array{int<0, max>, non-empty-string}>
     */
    protected static function validate(string $code): array
    {
        return \array_map(
            static fn(Violation $violation): array => [$violation->offset, $violation->message],
            (new Validator([static::createRule()]))->validate(self::parse($code)),
        );
    }
}
