<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Psl\Type;
use Psl\Type\Exception\AssertException;
use UnitEnum;

/** @mago-expect lint:cyclomatic-complexity */
final class Fields
{
    /** @var list<string> */
    public array $problems = [];

    /**
     * @param array<array-key, mixed> $data
     * @param list<string> $allowed
     */
    public function __construct(
        private readonly array $data,
        private readonly string $where,
        array $allowed,
    ) {
        foreach (array_keys($data) as $key) {
            if (in_array($key, $allowed, strict: true)) {
                continue;
            }

            $this->problems[] = "{$where}: unexpected field '{$key}'";
        }
    }

    /** @return null|non-empty-string */
    public function text(string $key): ?string
    {
        try {
            return Type\non_empty_string()->assert(trim(Type\string()->assert($this->data[$key] ?? null)));
        } catch (AssertException) {
            return $this->wrong("'{$key}' must be a text that is not empty");
        }
    }

    /** @return null|non-empty-string */
    public function optionalText(string $key): ?string
    {
        return $this->given($key) ? $this->text($key) : null;
    }

    public function number(string $key, int $most = PHP_INT_MAX): ?int
    {
        try {
            return Type\int_range(min: 1, max: $most)->assert($this->data[$key] ?? null);
        } catch (AssertException) {
            return $this->wrong(
                $most === PHP_INT_MAX
                    ? "'{$key}' must be a whole number from 1"
                    : "'{$key}' must be a whole number from 1 to {$most}",
            );
        }
    }

    public function optionalNumber(string $key): ?int
    {
        return $this->given($key) ? $this->number($key) : null;
    }

    /** @return array{int, int} */
    public function position(string $key): array
    {
        if (!$this->given($key)) {
            return [0, 0];
        }

        try {
            return Type\shape([Type\int(), Type\int()])->assert($this->data[$key]);
        } catch (AssertException) {
            $this->wrong("'{$key}' must be a pair of whole numbers, as in [120, -40]");

            return [0, 0];
        }
    }

    /**
     * @template T of UnitEnum
     *
     * @param list<T> $cases
     * @param null|T $otherwise
     *
     * @return null|T
     */
    public function choice(string $key, array $cases, ?UnitEnum $otherwise = null): ?UnitEnum
    {
        if ($otherwise !== null && !$this->given($key)) {
            return $otherwise;
        }

        foreach ($cases as $case) {
            if (($this->data[$key] ?? null) === $case->name) {
                return $case;
            }
        }

        return $this->wrong(
            "'{$key}' must be one of "
                . implode(', ', array_map(static fn(UnitEnum $case): string => $case->name, $cases)),
        );
    }

    private function given(string $key): bool
    {
        return ($this->data[$key] ?? null) !== null;
    }

    private function wrong(string $problem): null
    {
        $this->problems[] = "{$this->where}: {$problem}";

        return null;
    }
}
