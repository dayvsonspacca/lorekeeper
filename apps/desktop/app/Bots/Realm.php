<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Illuminate\Support\Carbon;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotRun;
use Lorekeeper\Apps\Desktop\Models\BotTask;
use Lorekeeper\Apps\Desktop\Native\LocalTime;

final readonly class Realm
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public int $online,
        public int $accounts,
        public int $secondsPlayed,
        public int $tasksDone,
        public int $tasksQueued,
        public int $gold,
        public int $troubles,
    ) {}

    /** @param list<Account> $accounts */
    public static function of(array $accounts, Carbon $now): self
    {
        $today = LocalTime::startOfToday($now);
        $online = count(array_filter(
            $accounts,
            static fn(Account $account): bool => $account->latestRun?->status->isActive() ?? false,
        ));

        $played = 0;
        foreach (BotRun::query()
            ->whereNotNull('started_at')
            ->where(static fn($query) => $query->whereNull('finished_at')->orWhere('finished_at', '>=', $today))
            ->get(['started_at', 'finished_at']) as $run) {
            $from = $run->started_at?->max($today) ?? $today;
            $played += (int) max(0, $from->diffInSeconds($run->finished_at ?? $now));
        }

        $since = $now->copy()->subDay();

        return new self(
            $online,
            count($accounts),
            $played,
            BotTask::query()
                ->where('status', TaskStatus::Done)
                ->where('finished_at', '>=', $today)
                ->count(),
            BotTask::query()->where('status', TaskStatus::Pending)->count(),
            (int) BotTask::query()->where('started_at', '>=', $today)->sum('gold'),
            BotTask::query()
                ->where('status', TaskStatus::Failed)
                ->where('finished_at', '>=', $since)
                ->count() + BotRun::query()
                ->whereIn('status', [RunStatus::Failed, RunStatus::Disconnected])
                ->where('finished_at', '>=', $since)
                ->count(),
        );
    }

    public function played(): string
    {
        $minutes = intdiv($this->secondsPlayed, num2: 60);

        return $minutes < 60 ? "{$minutes}m" : sprintf('%dh %02dm', intdiv($minutes, num2: 60), $minutes % 60);
    }
}
