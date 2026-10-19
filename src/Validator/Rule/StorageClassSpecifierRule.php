<?php

declare(strict_types=1);

namespace FFI\Parser\Validator\Rule;

use FFI\Parser\Node\NodeInterface;
use FFI\Parser\Node\Specifier\SpecifierList;
use FFI\Parser\Node\Specifier\StorageClassSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\AutoSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\ConstExprSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\ExternSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\RegisterSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\StaticSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\ThreadLocalSpecifier;
use FFI\Parser\Node\Specifier\StorageClassSpecifier\TypeDefSpecifier;
use FFI\Parser\Validator\ValidationContext;
use FFI\Parser\Validator\ValidatorRuleInterface;
use FFI\Parser\Validator\Violation;

/**
 * Checks the combination of the storage-class specifiers: at most one of
 * them, except `thread_local` with `static` or `extern`, `auto` with any but
 * `typedef` and `constexpr` with `auto`, `register` or `static`.
 * `typedef static int T;` and `static extern int x;` are not valid.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.7.2 Storage-class specifiers
 *
 * @template-implements ValidatorRuleInterface<SpecifierList>
 */
final class StorageClassSpecifierRule implements ValidatorRuleInterface
{
    /**
     * Keywords of the specifiers, by the class of the node.
     *
     * @var array<class-string<StorageClassSpecifier>, non-empty-string>
     */
    private const KEYWORDS = [
        AutoSpecifier::class => 'auto',
        ConstExprSpecifier::class => 'constexpr',
        ExternSpecifier::class => 'extern',
        RegisterSpecifier::class => 'register',
        StaticSpecifier::class => 'static',
        ThreadLocalSpecifier::class => 'thread_local',
        TypeDefSpecifier::class => 'typedef',
    ];

    public function getNodeClasses(): iterable
    {
        return [SpecifierList::class];
    }

    public function validate(NodeInterface $node, ValidationContext $context): iterable
    {
        $storage = [];

        foreach ($node as $specifier) {
            if ($specifier instanceof StorageClassSpecifier) {
                $storage[] = $specifier;
            }
        }

        if ($storage === []) {
            return [];
        }

        $classes = \array_map(static fn(StorageClassSpecifier $specifier): string => $specifier::class, $storage);

        if ($this->isValidCombination($classes)) {
            return [];
        }

        return [
            new Violation(
                \sprintf('Invalid combination of storage-class specifiers `%s`', $this->spell($classes)),
                $storage[0]->offset,
            ),
        ];
    }

    /**
     * @param non-empty-list<class-string<StorageClassSpecifier>> $classes
     */
    private function isValidCombination(array $classes): bool
    {
        if (\count($classes) !== \count(\array_unique($classes))) {
            return false;
        }

        $isAuto = \in_array(AutoSpecifier::class, $classes, true);

        if ($isAuto && \in_array(TypeDefSpecifier::class, $classes, true)) {
            return false;
        }

        $others = \array_values(\array_diff($classes, [AutoSpecifier::class]));

        return match (\count($others)) {
            0, 1 => true,
            2 => $this->isPair($others, ThreadLocalSpecifier::class, [StaticSpecifier::class, ExternSpecifier::class])
                || $this->isPair($others, ConstExprSpecifier::class, [RegisterSpecifier::class, StaticSpecifier::class]),
            default => false,
        };
    }

    /**
     * Whether the two specifiers are the given one and one of its partners.
     *
     * @param list<class-string<StorageClassSpecifier>> $classes
     * @param class-string<StorageClassSpecifier> $class
     * @param list<class-string<StorageClassSpecifier>> $partners
     */
    private function isPair(array $classes, string $class, array $partners): bool
    {
        return \in_array($class, $classes, true) && \array_intersect($partners, $classes) !== [];
    }

    /**
     * @param non-empty-list<class-string<StorageClassSpecifier>> $classes
     * @return non-empty-string
     */
    private function spell(array $classes): string
    {
        return \implode(' ', \array_map(static fn(string $class): string => self::KEYWORDS[$class], $classes));
    }
}
