<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

final readonly class RoutineSteps
{
    /** @return list<array{script: string, parameters: array<string, mixed>}> */
    public static function levelUp(int $from, int $to, int $every, ?string $enhancement): array
    {
        $to = max(2, min(100, $to));
        $every = max(1, $every);
        $steps = [];
        for ($level = max(2, $from);; $level = min($to, $level + $every)) {
            $steps[] = ['script' => BotScript::LevelUp->value, 'parameters' => ['level' => $level]];
            if ($enhancement !== null) {
                $steps[] = ['script' => BotScript::Enhance->value, 'parameters' => ['type' => $enhancement]];
            }

            if ($level >= $to) {
                return $steps;
            }
        }
    }
}
