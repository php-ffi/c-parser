<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule\IntegerTypeRule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\RecordType\MemberDeclaration;
use FFI\Parser\Validator\Rule\IntegerTypeRule;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\Violation;

/**
 * Checks that a bit-field has an integer type: `float x : 3;` and
 * `int *p : 3;` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.2 Structure and union specifiers
 *
 * @template-extends IntegerTypeRule<MemberDeclaration>
 */
final class BitFieldRule extends IntegerTypeRule
{
    public function getNodeClasses(): iterable
    {
        return [MemberDeclaration::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if ($node->width === null) {
            return [];
        }

        if (self::isIntegerBase(self::findBaseType($node->type))) {
            return [];
        }

        return [new Violation('Bit-field should have an integer type', $node->offset)];
    }
}
