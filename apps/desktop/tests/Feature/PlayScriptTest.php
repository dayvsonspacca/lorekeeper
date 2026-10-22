<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Bots\PlayScript;
use Lorekeeper\Apps\Desktop\Models\Account;
use Lorekeeper\Apps\Desktop\Models\BotLog;
use Lorekeeper\Apps\Desktop\Models\Script;
use Lorekeeper\Apps\Desktop\Scripts\FlowFile;
use Lorekeeper\Apps\Desktop\Tests\Characters;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Script\OutcomeStatus;
use PHPUnit\Framework\Attributes\Test;
use Psl\Async\NullCancellationToken;
use Psl\TCP;

final class PlayScriptTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_bot_with_no_script_plays_nothing(): void
    {
        static::assertNull(PlayScript::next($this->account(), $this->client(), new NullCancellationToken()));
    }

    #[Test]
    public function a_bot_plays_the_script_it_was_given_and_lets_it_go(): void
    {
        $account = $this->account();
        $script = $this->script('{"id": "high", "do": "level", "level": 30}');
        $account->update(['script_id' => $script->id]);

        $outcome = PlayScript::next($account, $this->client(), new NullCancellationToken());

        static::assertSame(OutcomeStatus::Done, $outcome?->status);
        static::assertNull($account->refresh()->script_id);
        static::assertNull($account->step);
        static::assertSame(
            [
                ['Playing Level check',                          'info'],
                ['→ Level 30 or higher',                         'info'],
                ['Level check: ended after: Level 30 or higher', 'success'],
            ],
            $this->logs($account),
        );
    }

    #[Test]
    public function a_script_that_cannot_play_is_let_go_with_its_problems(): void
    {
        $account = $this->account();
        $script = $this->script('{"id": "high", "do": "level", "level": 30}');
        $script->update(['drawing' => [...$script->drawing, 'start' => 'gone']]);
        $account->update(['script_id' => $script->id]);

        static::assertNull(PlayScript::next($account, $this->client(), new NullCancellationToken()));
        static::assertNull($account->refresh()->script_id);
        static::assertSame(
            [["Level check cannot play: the file: 'start' names no node ('gone')", 'error']],
            $this->logs($account),
        );
    }

    #[Test]
    public function a_bot_that_logs_out_lets_its_script_go(): void
    {
        $account = $this->account();
        $account->update([
            'status' => BotStatus::Online,
            'script_id' => $this->script('{"id": "high", "do": "level", "level": 30}')->id,
            'step' => 'level 30 or higher',
        ]);

        $account->settle(BotStatus::Offline, null);

        static::assertNull($account->script_id);
        static::assertNull($account->step);
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }

    private function script(string $node): Script
    {
        $script = Script::fromFlow(FlowFile::parse(
            "{\"lorekeeper\": 1, \"name\": \"Level check\", \"start\": \"high\", \"nodes\": [{$node}]}",
        ));
        $script->save();

        return $script;
    }

    private function client(): Client
    {
        return new Client(
            new Connection(static::createStub(TCP\StreamInterface::class)),
            Characters::me('Hero', level: 4, map: 'battleon'),
        );
    }

    /** @return list<array{string, string}> */
    private function logs(Account $account): array
    {
        return $account
            ->logs()
            ->orderBy('id')
            ->get()
            ->map(static fn(BotLog $log): array => [$log->message, $log->level->value])
            ->values()
            ->all();
    }
}
