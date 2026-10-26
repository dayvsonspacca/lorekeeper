<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Item\ItemEquippedEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use Lorekeeper\Server\Message\XmlMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EventsTest extends TestCase
{
    private const string ARENA_OF_A_MONSTER_TOO_HIGH = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"moveToArea","areaId":732692,"areaName":"icestormarena-48213","intType":"1","sExtra":"NoAfk","strMapName":"icestormarena","strMapFileName":"Icewing/town-icestormarena-21Jul23.swf","monmap":[{"MonMapID":1,"MonID":3984,"strFrame":"r4","bRed":false,"intRSS":-1}],"mondef":[{"sRace":"Undead","MonID":3984,"intLevel":300,"strLinkage":"UndeadMage1","strMonName":"Skeletal Ice Mage","strMonFileName":"UndeadMage1.swf","strBehave":"walk"}],"uoBranch":[],"monBranch":[{"MonMapID":1,"MonID":3984,"intState":1,"intHP":730,"intMP":100,"intHPMax":730,"intMPMax":100,"bRed":false,"iLvl":5,"wDPS":5}]}}}
        JSON;

    #[Test]
    public function a_value_the_core_refuses_makes_the_event_malformed(): void
    {
        $recognized = Events::recognize(JsonMessage::from(self::ARENA_OF_A_MONSTER_TOO_HIGH)->unwrap());

        static::assertCount(1, $recognized);
        static::assertInstanceOf(MalformedEventException::class, $recognized[0]);
        static::assertSame(MovedToMapEvent::class, $recognized[0]->event);
        static::assertNotNull($recognized[0]->getPrevious());
    }

    #[Test]
    public function it_knows_every_event_of_the_package(): void
    {
        $files = glob(__DIR__ . '/../../src/Event/{,*/,*/*/}*Event.php', GLOB_BRACE);
        $concrete = array_values(array_filter(
            array_map(
                static fn(string $file): string => basename($file, suffix: '.php'),
                $files === false ? [] : $files,
            ),
            static fn(string $class): bool => !in_array(
                $class,
                [
                    'Event',
                    'NoticeEvent',
                    'CombatRoundEvent',
                    'InventoryUpdatedEvent',
                    'RewardedEvent',
                    'MapJoinedEvent',
                    'MapRefusedEvent',
                ],
                strict: true,
            ),
        ));
        $known = array_map(static fn(string $event): string => substr(
            $event,
            (int) strrpos($event, needle: '\\') + 1,
        ), Events::ALL);

        sort($concrete);
        sort($known);

        static::assertSame($concrete, $known);
        foreach (Events::ALL as $event) {
            static::assertTrue(is_subclass_of($event, Event::class));
        }
    }

    #[Test]
    public function it_recognizes_what_a_message_is(): void
    {
        $policy = Events::recognize(XmlMessage::from('<cross-domain-policy/>')->unwrap());
        $equip = Events::recognize(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":1,"ItemID":2}}}')->unwrap());

        static::assertCount(1, $policy);
        static::assertInstanceOf(ConnectionEstablishedEvent::class, $policy[0]);
        static::assertInstanceOf(ItemEquippedEvent::class, $equip[0]);
        static::assertSame([], Events::recognize(DelimitedMessage::from('%xt%clearAuras%-1%')->unwrap()));
    }

    #[Test]
    public function it_reports_messages_it_cannot_read_instead_of_failing(): void
    {
        $recognized = Events::recognize(JsonMessage::from('{"b":{"o":{"cmd":"equipItem","uid":1}}}')->unwrap());

        static::assertCount(1, $recognized);
        static::assertInstanceOf(MalformedEventException::class, $recognized[0]);
    }
}
