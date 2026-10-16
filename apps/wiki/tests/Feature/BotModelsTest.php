<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Models\Account;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class BotModelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function account_passwords_are_encrypted_at_rest_and_never_serialized(): void
    {
        $secret = bin2hex(random_bytes(8));
        $account = Account::query()->create(['name' => 'Hero', 'password' => $secret]);

        $stored = DB::table('accounts')->value('password');

        static::assertIsString($stored);
        static::assertStringNotContainsString($secret, $stored);
        static::assertSame($secret, $account->fresh()?->password);
        static::assertArrayNotHasKey('password', $account->toArray());
    }

    #[Test]
    public function runs_start_pending_and_keep_their_log(): void
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
        $run = $account->runs()->create(['script' => BotScript::StayInMap, 'parameters' => ['map' => 'battleon']]);
        $run = BotRun::query()->findOrFail($run->id);

        $run->log('> login');
        $run->log('< logged in');

        static::assertSame(RunStatus::Pending, $run->status);
        static::assertSame(BotScript::StayInMap, $run->script);
        static::assertSame(['map' => 'battleon'], $run->parameters);
        static::assertSame('Hero', $run->account->name);
        static::assertSame(['> login', '< logged in'], $run->logs()->orderBy('id')->pluck('message')->all());
    }

    #[Test]
    public function only_pending_running_and_stopping_runs_are_active(): void
    {
        static::assertSame(
            [RunStatus::Pending, RunStatus::Running, RunStatus::Stopping],
            array_values(array_filter(RunStatus::cases(), static fn(RunStatus $status): bool => $status->isActive())),
        );
    }
}
