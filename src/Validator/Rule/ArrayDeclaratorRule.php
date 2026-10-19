<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\ArrayType;
use FFI\Parser\Node\Type\AttributedType;
use FFI\Parser\Node\Type\BuiltinType\VoidType;
use FFI\Parser\Node\Type\FunctionType;
use FFI\Parser\Node\Type\QualifiedType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that the element type of an array is neither a function nor
 * `void`: `int a[3](void)` and `void a[3]` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.7.3 Array declarators
 *
 * @template-implements ValidatorRuleInterface<ArrayType>
 */
final class ArrayDeclaratorRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [ArrayType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if ($node->type instanceof FunctionType) {
            return [new Violation('Element type of an array should not be a function', $node->offset)];
        }

        if ($this->isVoid($node->type)) {
            return [new Violation('Element type of an array should not be `void`', $node->offset)];
        }

        return [];
    }

    private function isVoid(?TypeInterface $type): bool
    {
        while ($type instanceof QualifiedType || $type instanceof AttributedType) {
            $type = $type->type;
        }

        return $type instanceof VoidType;
    }
}
