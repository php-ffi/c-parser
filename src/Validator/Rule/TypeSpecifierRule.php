<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Type\AtomicType;
use FFI\Parser\Node\Type\BuiltinType\BitIntType;
use FFI\Parser\Node\Type\BuiltinType\BoolType;
use FFI\Parser\Node\Type\BuiltinType\CharType;
use FFI\Parser\Node\Type\BuiltinType\DecimalNType;
use FFI\Parser\Node\Type\BuiltinType\DecimalNxType;
use FFI\Parser\Node\Type\BuiltinType\DoubleType;
use FFI\Parser\Node\Type\BuiltinType\FloatNType;
use FFI\Parser\Node\Type\BuiltinType\FloatNxType;
use FFI\Parser\Node\Type\BuiltinType\FloatType;
use FFI\Parser\Node\Type\BuiltinType\IntType;
use FFI\Parser\Node\Type\BuiltinType\VoidType;
use FFI\Parser\Node\Type\EnumType;
use FFI\Parser\Node\Type\ModifiedType;
use FFI\Parser\Node\Type\ModifiedType\TypeModifier;
use FFI\Parser\Node\Type\NamedType;
use FFI\Parser\Node\Type\RecordType\StructType;
use FFI\Parser\Node\Type\RecordType\UnionType;
use FFI\Parser\Node\Type\TypeInterface;
use FFI\Parser\Node\Type\TypeOfType;
use FFI\Parser\Node\Type\TypeOfTypeType;
use FFI\Parser\Node\Type\TypeOfUnqualType;
use FFI\Parser\Node\Type\TypeOfUnqualTypeType;
use FFI\Parser\Validator\Rule\Extension\ComplexIntegerTypeRule;
use FFI\Parser\Validator\Rule\Extension\ModifiedTypedefNameRule;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks that the type specifiers of a type with modifiers are one of the
 * multisets of C23: `unsigned long long int`, `long double _Complex`, but
 * not `long float`, `unsigned double` or `long long long`. A complex
 * integer type, `_Complex int`, and a typedef name with a modifier,
 * `T long`, are checked by {@see ComplexIntegerTypeRule} and
 * {@see ModifiedTypedefNameRule}.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.3.1 General
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex G.2 Types
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), Annex H.7 Declarations
 *
 * @template-implements ValidatorRuleInterface<ModifiedType>
 */
final class TypeSpecifierRule implements ValidatorRuleInterface
{
    public function getNodeClasses(): iterable
    {
        return [ModifiedType::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        if ($this->isValidCombination($node->modifiers, $node->type)) {
            return [];
        }

        $keywords = \array_map(static fn(TypeModifier $modifier): string => $modifier->value, $node->modifiers);

        if ($node->type !== null) {
            $keywords[] = $this->spellType($node->type);
        }

        return [
            new Violation(
                \sprintf('Invalid combination of type specifiers `%s`', \implode(' ', $keywords)),
                $node->offset,
            ),
        ];
    }

    /**
     * @param non-empty-list<TypeModifier> $modifiers
     */
    private function isValidCombination(array $modifiers, ?TypeInterface $type): bool
    {
        $counts = \array_count_values(\array_map(
            static fn(TypeModifier $modifier): string => $modifier->value,
            $modifiers,
        ));

        $sign = ($counts[TypeModifier::Signed->value] ?? 0) + ($counts[TypeModifier::Unsigned->value] ?? 0);
        $short = $counts[TypeModifier::Short->value] ?? 0;
        $long = $counts[TypeModifier::Long->value] ?? 0;
        $complex = $counts[TypeModifier::Complex->value] ?? 0;
        $imaginary = $counts[TypeModifier::Imaginary->value] ?? 0;

        if ($sign > 1 || $short > 1 || $long > 2 || $complex + $imaginary > 1 || ($short > 0 && $long > 0)) {
            return false;
        }

        return match (true) {
            $type === null, $type instanceof IntType => $imaginary === 0,
            $type instanceof CharType => $short + $long + $imaginary === 0,
            $type instanceof BitIntType => $short + $long + $complex + $imaginary === 0,
            $type instanceof FloatType,
            $type instanceof FloatNType,
            $type instanceof FloatNxType => $sign + $short + $long === 0,
            $type instanceof DoubleType => $sign + $short === 0 && $long <= 1,
            $type instanceof NamedType => true,
            default => false,
        };
    }

    /**
     * @return non-empty-string
     */
    private function spellType(TypeInterface $type): string
    {
        return match (true) {
            $type instanceof VoidType => 'void',
            $type instanceof CharType => 'char',
            $type instanceof IntType => 'int',
            $type instanceof FloatType => 'float',
            $type instanceof DoubleType => 'double',
            $type instanceof BoolType => 'bool',
            $type instanceof BitIntType => '_BitInt(...)',
            $type instanceof DecimalNType => '_Decimal' . $type->width,
            $type instanceof DecimalNxType => '_Decimal' . $type->width . 'x',
            $type instanceof FloatNType => '_Float' . $type->width,
            $type instanceof FloatNxType => '_Float' . $type->width . 'x',
            $type instanceof StructType => 'struct',
            $type instanceof UnionType => 'union',
            $type instanceof EnumType => 'enum',
            $type instanceof AtomicType => '_Atomic(...)',
            $type instanceof TypeOfType, $type instanceof TypeOfTypeType => 'typeof(...)',
            $type instanceof TypeOfUnqualType, $type instanceof TypeOfUnqualTypeType => 'typeof_unqual(...)',
            $type instanceof NamedType => $type->name->value,
            default => '...',
        };
    }
}
