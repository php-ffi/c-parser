<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\IntegerTypeRule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\TagDeclaration;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\BuiltinType\BitIntType;
use FFI\Parser\Node\Type\EnumType;
use FFI\Parser\Validator\Rule\IntegerTypeRule;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\Violation;

/**
 * Checks the underlying type of an enumeration: it is an integer type other
 * than an enumeration and a bit-precise one. `enum E : float { A }` and
 * `enum E : int x;` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.3 Enumeration specifiers
 *
 * @template-extends IntegerTypeRule<EnumType>
 */
final class EnumTypeRule extends IntegerTypeRule
{
    public function getNodeClasses(): iterable
    {
        return [EnumType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if ($node->underlyingType === null) {
            return [];
        }

        return $this->validateEnum($node, $context);
    }

    /**
     * @return iterable<mixed, Violation>
     */
    private function validateEnum(EnumType $node, ValidationContext $context): iterable
    {
        $base = self::findBaseType($node->underlyingType);

        if ($base instanceof EnumType || $base instanceof BitIntType) {
            yield new Violation(
                'Underlying type of an enumeration should not be an enumeration or a bit-precise integer type',
                $node->offset,
            );
        } elseif (!self::isIntegerBase($base)) {
            yield new Violation('Underlying type of an enumeration should be an integer type', $node->offset);
        }

        $parent = \array_last($context->parents);

        if ($node->enumerators === null
            && !($parent instanceof TagDeclaration && $parent->type === $node && $parent->specifiers === null)
        ) {
            yield new Violation(
                'Enumeration with an underlying type and without enumerators is allowed only in the declaration '
                    . 'of its tag',
                $node->offset,
            );
        }
    }
}
