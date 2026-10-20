<?php

declare(strict_types=1);

namespace FFI\Parser\Transformer\TransformationContext;

/**
 * Ordinary identifiers declared so far, in the file scope and in the function
 * prototype scopes.
 *
 * @link https://www.open-std.org/jtc1/sc22/wg14/www/docs/n3220.pdf C23 (N3220), 6.2.1 Scopes of identifiers
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal FFI\Parser\Transformer
 */
final class Scope
{
    /**
     * @var non-empty-list<array<string, bool>>
     */
    private array $scopes;

    /**
     * @param array<string, true> $typedefNames
     */
    public function __construct(array $typedefNames = [])
    {
        $this->scopes = [$typedefNames];
    }

    public function enter(): void
    {
        $this->scopes[] = [];
    }

    public function leave(): void
    {
        if (\count($this->scopes) > 1) {
            \array_pop($this->scopes);
        }
    }

    public function declareTypedefName(string $name): void
    {
        $this->scopes[\array_key_last($this->scopes)][$name] = true;
    }

    public function declareNonTypeName(string $name): void
    {
        $this->scopes[\array_key_last($this->scopes)][$name] = false;
    }

    public function isTypedefName(string $name): bool
    {
        return $this->findIsTypedefName($name) === true;
    }

    public function isNonTypeName(string $name): bool
    {
        return $this->findIsTypedefName($name) === false;
    }

    /**
     * @return bool|null whether the innermost declaration of the name is a
     *         typedef name, {@see null} in case of the name is not declared
     */
    private function findIsTypedefName(string $name): ?bool
    {
        for ($i = \count($this->scopes) - 1; $i >= 0; --$i) {
            if (isset($this->scopes[$i][$name])) {
                return $this->scopes[$i][$name];
            }
        }

        return null;
    }
}
