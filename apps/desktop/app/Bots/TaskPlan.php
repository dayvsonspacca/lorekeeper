<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

final readonly class TaskPlan
{
    /**
     * @param array<array-key, mixed> $tasks
     *
     * @return list<array{script: BotScript, parameters: array<string, mixed>}>
     */
    public static function listed(array $tasks): array
    {
        $listed = [];
        foreach ($tasks as $task) {
            $task = is_array($task) ? $task : [];
            $script = self::script($task['script'] ?? null);
            if ($script !== null) {
                $listed[] = ['script' => $script, 'parameters' => self::parameters($task['parameters'] ?? null)];
            }
        }

        return $listed;
    }

    /**
     * @param list<array{script: BotScript, parameters: array<string, mixed>}> $tasks
     *
     * @return list<array{script: BotScript, parameters: array<string, mixed>}>
     */
    public static function trimmed(array $tasks, ?int $level): array
    {
        $kept = [];
        foreach ($tasks as $task) {
            $settled = $level !== null && $task['script']->settledAt($task['parameters'], $level) !== null;
            if (!$settled && $task !== ($kept[count($kept) - 1] ?? null)) {
                $kept[] = $task;
            }
        }

        return $kept;
    }

    private static function script(mixed $chosen): ?BotScript
    {
        return $chosen instanceof BotScript ? $chosen : BotScript::tryFrom(is_string($chosen) ? $chosen : '');
    }

    /** @return array<string, mixed> */
    private static function parameters(mixed $parameters): array
    {
        return is_array($parameters) ? array_filter($parameters, static fn(mixed $value): bool => $value !== null) : [];
    }
}
