<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotTask;

final readonly class Realm
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public int $online,
        public int $accounts,
        public int $experiencePerHour,
        public int $kills,
        public int $gold,
        public int $levels,
    ) {}

    /** @param list<Account> $accounts */
    public static function of(array $accounts, int $now): self
    {
        $online = 0;
        $pace = 0;
        $kills = 0;
        foreach ($accounts as $account) {
            $run = $account->latestRun;
            if ($run === null || !$run->status->isActive()) {
                continue;
            }

            $online++;
            $scene = $run->scene();
            $pace += $scene?->pulse->experiencePerHour($now) ?? 0;
            $kills += array_sum($scene?->pulse->kills($now) ?? []);
        }

        $today = BotTask::query()->where('started_at', '>=', now()->startOfDay())->get([
            'gold',
            'level_from',
            'level_to',
        ]);

        return new self(
            $online,
            count($accounts),
            $pace,
            $kills,
            (int) $today->sum('gold'),
            (int) $today->sum(static fn(BotTask $task): int => max(
                0,
                ($task->level_to ?? 0) - ($task->level_from ?? 0),
            )),
        );
    }
}
