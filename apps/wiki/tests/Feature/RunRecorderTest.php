<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Wiki\Bots\LogLevel;
use Lorekeeper\Apps\Wiki\Bots\RunRecorder;
use Lorekeeper\Apps\Wiki\Models\Account;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunLog;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use Lorekeeper\Client\Command\LogoutCommand;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use Psl\DateTime\Duration;

final class RunRecorderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_keeps_every_event_the_run_hears(): void
    {
        $run = $this->botRun();
        $recorder = new RunRecorder($run);

        $recorder->heard(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":7,"ItemID":31660}}}')->unwrap());
        $recorder->heard(DelimitedMessage::from('%xt%uotls%-1%hero%afk:true%')->unwrap());
        $recorder->heard(JsonMessage::from('{"b":{"o":{"cmd":"loadPrefs"}}}')->unwrap());
        $recorder->heard(XmlMessage::from("<msg t='sys'><body action='uER' r='1'/></msg>")->unwrap());
        $recorder->heard(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":7}}}')->unwrap());

        $events = $run->events()->orderBy('id')->get();

        static::assertSame(
            ['ItemEquipped', 'Unrecognized', 'Unrecognized', 'Unrecognized', 'Malformed'],
            $events->pluck('name')->all(),
        );
        static::assertSame('Socket 7 equipped item 31660', $events[0]->summary);
        static::assertNull($events[0]->raw);
        static::assertSame(['uotls', 'loadPrefs', 'uER'], [
            $events[1]->summary,
            $events[2]->summary,
            $events[3]->summary,
        ]);
        static::assertSame('%xt%uotls%-1%hero%afk:true%', $events[1]->raw);
        static::assertSame(LogLevel::Error, $run->logs()->sole()->level);
    }

    #[Test]
    public function it_writes_one_line_for_a_long_idle(): void
    {
        $run = $this->botRun();
        $recorder = new RunRecorder($run);

        $recorder->stepped(new Pause(Duration::minutes(1)), null);
        $recorder->heard(
            JsonMessage::from(
                '{"b":{"o":{"cmd":"moveToArea","areaId":1,"areaName":"forest-1","strMapName":"forest","strMapFileName":"forest.swf","uoBranch":[],"monBranch":[]}}}',
            )->unwrap(),
        );
        $recorder->stepped(new Send(new LogoutCommand()), null);
        $recorder->stepped(new Pause(Duration::minutes(1)), null);
        $recorder->stepped(new Pause(Duration::minutes(1)), null);

        static::assertSame(
            ['Idling', 'Logged out', 'Idling in forest'],
            $run
                ->logs()
                ->orderBy('id')
                ->get()
                ->map(static fn(BotRunLog $log): string => $log->message)
                ->all(),
        );
    }

    private function botRun(): BotRun
    {
        $account = Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);

        return $account->runs()->create();
    }
}
