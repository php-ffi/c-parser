<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\FunctionDeclaration;
use FFI\Parser\Node\Declaration\SpecifiedDeclaration\DeclaratorDeclaration\TypeDefDeclaration;
use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that an initializer is written only in the declaration of an
 * object: `int f(void) = 0;` and `typedef int T = 0;` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.11 Initialization
 *
 * @template-implements ValidatorRuleInterface<FunctionDeclaration|TypeDefDeclaration>
 */
final class InitializerRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [FunctionDeclaration::class, TypeDefDeclaration::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $kind = match (true) {
            $node instanceof FunctionDeclaration => 'a function',
            $node instanceof TypeDefDeclaration => 'a typedef name',
        };

        if ($node->initializer === null) {
            return [];
        }

        return [
            new Violation(
                \sprintf('Initializer is not allowed in the declaration of %s', $kind),
                $node->initializer->offset,
            ),
        ];
    }
}
