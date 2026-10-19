<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\ArrayType;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a function declarator returns neither a function nor an
 * array: `int f(void)(void)` and `int f(void)[3]` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.4 Function declarators
 *
 * @template-implements ValidatorRuleInterface<FunctionType>
 */
final class FunctionDeclaratorRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [FunctionType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $kind = match (true) {
            $node->returnType instanceof FunctionType => 'a function',
            $node->returnType instanceof ArrayType => 'an array',
            default => null,
        };

        if ($kind === null) {
            return [];
        }

        $length = \max(0, $node->returnType->offset - $node->offset);

        yield new Violation(\sprintf('Function should not return %s', $kind), $node->offset, $length);
    }
}
