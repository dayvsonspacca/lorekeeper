<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotLog;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class BotLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function playing_for_long_keeps_only_the_newest_lines(): void
    {
        $hero = $this->account('Hero');

        foreach (range(1, BotLog::KEEP + BotLog::EVERY) as $line) {
            $hero->log("Line {$line}");
        }

        static::assertSame(BotLog::KEEP, $hero->logs()->count());
        static::assertSame('Line ' . (BotLog::EVERY + 1), $hero->logs()->oldest('id')->value('message'));
        static::assertSame('Line ' . (BotLog::KEEP + BotLog::EVERY), $hero->logs()->latest('id')->value('message'));
    }

    #[Test]
    public function trimming_spares_the_other_accounts(): void
    {
        $hero = $this->account('Hero');
        $other = $this->account('Hero2');
        $this->fill($hero, BotLog::KEEP + 10);
        $this->fill($other, 10);

        BotLog::trim($hero->id);

        static::assertSame(BotLog::KEEP, $hero->logs()->count());
        static::assertSame(10, $other->logs()->count());
    }

    #[Test]
    public function trimming_every_account_bounds_each_of_them(): void
    {
        $hero = $this->account('Hero');
        $other = $this->account('Hero2');
        $this->fill($hero, BotLog::KEEP + 10);
        $this->fill($other, BotLog::KEEP + 20);

        BotLog::trimAll();

        static::assertSame(BotLog::KEEP, $hero->logs()->count());
        static::assertSame(BotLog::KEEP, $other->logs()->count());
    }

    private function account(string $name): Account
    {
        return Account::query()->create(['name' => $name, 'password' => bin2hex(random_bytes(8))]);
    }

    private function fill(Account $account, int $lines): void
    {
        BotLog::query()->insert(array_map(
            static fn(int $line): array => [
                'account_id' => $account->id,
                'message' => "Line {$line}",
                'level' => 'info',
                'created_at' => now(),
            ],
            range(1, $lines),
        ));
    }
}
