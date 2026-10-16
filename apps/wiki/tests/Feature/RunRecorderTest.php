<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Wiki\Bots\LogLevel;
use Lorekeeper\Apps\Wiki\Bots\RunRecorder;
use Lorekeeper\Apps\Wiki\Models\Account;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\BotRunEvent;
use Lorekeeper\Apps\Wiki\Models\BotRunLog;
use Lorekeeper\Apps\Wiki\Models\Player;
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
        $recorder->heard(DelimitedMessage::from('%xt%clearAuras%-1%')->unwrap());
        $recorder->heard(JsonMessage::from('{"b":{"o":{"cmd":"loadPrefs"}}}')->unwrap());
        $recorder->heard(
            XmlMessage::from("<msg t='sys'><body action='userGone' r='2'><user id='1' /></body></msg>")->unwrap(),
        );
        $recorder->heard(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":7}}}')->unwrap());

        $events = $run->events()->orderBy('id')->get();

        static::assertSame(
            ['ItemEquipped', 'Unrecognized', 'Unrecognized', 'Unrecognized', 'Malformed'],
            $events->pluck('name')->all(),
        );
        static::assertSame('Socket 7 equipped item 31660', $events[0]->summary);
        static::assertNull($events[0]->raw);
        static::assertSame(['clearAuras', 'loadPrefs', 'userGone'], [
            $events[1]->summary,
            $events[2]->summary,
            $events[3]->summary,
        ]);
        static::assertSame('%xt%clearAuras%-1%', $events[1]->raw);
        static::assertSame(LogLevel::Error, $run->logs()->sole()->level);
    }

    #[Test]
    public function it_saves_the_players_it_hears_about(): void
    {
        $recorder = new RunRecorder($this->botRun());

        $recorder->heard(
            XmlMessage::from(
                "<msg t='sys'><body action='joinOK' r='1'><uLs r='1'><u i='1'><n><![CDATA[hero]]></n></u></uLs></body></msg>",
            )->unwrap(),
        );
        $recorder->heard(
            JsonMessage::from(
                '{"b":{"o":{"cmd":"moveToArea","areaId":1,"areaName":"battleon-1","strMapName":"battleon","strMapFileName":"town.swf","uoBranch":[{"strUsername":"Hero"},{"strUsername":"Hero 2"}],"monBranch":[]}}}',
            )->unwrap(),
        );
        $recorder->heard(
            XmlMessage::from(
                "<msg t='sys'><body action='uER' r='1'><u i='3'><n><![CDATA[hero 3]]></n></u></body></msg>",
            )->unwrap(),
        );
        $recorder->heard(DelimitedMessage::from('%xt%uotls%-1%hero 2%afk:true%')->unwrap());
        $recorder->heard(DelimitedMessage::from('%xt%exitArea%-1%4%hero 4%')->unwrap());

        static::assertSame(
            ['Hero', 'Hero 2', 'hero 3', 'hero 4'],
            Player::query()
                ->orderBy('id')
                ->pluck('name')
                ->all(),
        );
        static::assertSame(
            [null],
            Player::query()
                ->distinct()
                ->pluck('aqw_id')
                ->all(),
        );
        static::assertSame(
            ['RoomJoined', 'MovedToMap', 'PlayerEntered', 'PlayerUpdated', 'PlayerLeft'],
            BotRunEvent::query()
                ->orderBy('id')
                ->pluck('name')
                ->all(),
        );
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
