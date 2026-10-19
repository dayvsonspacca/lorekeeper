<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

final readonly class Pulse
{
    public const int MINUTES = 30;

    /** @param array<int, array{int, int}> $minutes */
    public function __construct(
        public array $minutes = [],
    ) {}

    public function rewarded(int $now, int $experience): self
    {
        $minute = intdiv($now, num2: 60);
        $minutes = array_filter(
            $this->minutes,
            static fn(int $at): bool => $at > ($minute - self::MINUTES),
            ARRAY_FILTER_USE_KEY,
        );
        [$earned, $kills] = $minutes[$minute] ?? [0, 0];
        $minutes[$minute] = [$earned + $experience, $kills + 1];

        return new self($minutes);
    }

    /** @return list<int> */
    public function experience(int $now): array
    {
        return $this->column($now, 0);
    }

    /** @return list<int> */
    public function kills(int $now): array
    {
        return $this->column($now, 1);
    }

    public function experiencePerHour(int $now): int
    {
        $finished = array_slice($this->experience($now), offset: -11, length: 10);
        while ($finished !== [] && $finished[0] === 0) {
            array_shift($finished);
        }

        return $finished === [] ? 0 : (int) round((array_sum($finished) / count($finished)) * 60);
    }

    /** @return list<int> */
    private function column(int $now, int $column): array
    {
        $minute = intdiv($now, num2: 60);
        $values = [];
        for ($at = $minute - self::MINUTES + 1; $at <= $minute; $at++) {
            $values[] = $this->minutes[$at][$column] ?? 0;
        }

        return $values;
    }
}
