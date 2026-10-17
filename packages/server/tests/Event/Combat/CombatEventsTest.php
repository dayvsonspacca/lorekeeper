<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Combat;

use Lorekeeper\Server\Event\Combat\AttackRejectedEvent;
use Lorekeeper\Server\Event\Combat\CombatState;
use Lorekeeper\Server\Event\Combat\CombatUpdatedEvent;
use Lorekeeper\Server\Event\Combat\GoldExpGainedEvent;
use Lorekeeper\Server\Event\Combat\LevelUpEvent;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\Skill;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CombatEventsTest extends TestCase
{
    #[Test]
    public function it_reads_the_skills_of_the_class(): void
    {
        $event = SkillsLoadedEvent::from(JsonMessage::from(<<<'JSON'
            {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":89,"nam":"Auto Attack","typ":"aa","ref":"aa","tgt":"h","fx":"m","damage":0.9,"range":301,"mp":0,"cd":2000,"auto":true,"isOK":true},{"id":115,"nam":"Fireball","typ":"m","ref":"a1","tgt":"h","tgtMax":1,"mp":20,"cd":6000,"isOK":true},{"id":116,"nam":"Ice Shard","typ":"m","ref":"a2","tgt":"h","mp":15,"cd":6000,"isOK":false},{"id":118,"nam":"Arcane Shield","typ":"m","ref":"a4","tgt":"s","mp":0,"cd":20000,"isOK":false},{"nam":"Potions","typ":"i","ref":"i1","tgt":"f","mp":0,"cd":60000,"isOK":true}],"passive":[{"nam":"Hot Headed","ref":"p1"}]}}}}
            JSON)->unwrap())->unwrap();

        static::assertSame(
            [
                ['aa', true,  0,  2000,   true],
                ['a1', true,  20, 6000,   true],
                ['a2', true,  15, 6000,   false],
                ['a4', false, 0,  20_000, false],
                ['i1', false, 0,  60_000, true],
            ],
            array_map(static fn(Skill $skill): array => [
                $skill->ref,
                $skill->hostile,
                $skill->mana->value,
                (int) $skill->cooldown->getTotalMilliseconds(),
                $skill->usable,
            ], $event->skills),
        );
    }

    #[Test]
    public function it_reads_what_a_combat_tick_changed(): void
    {
        $event = CombatUpdatedEvent::from(JsonMessage::from(<<<'JSON'
            {"t":"xt","b":{"r":-1,"o":{"cmd":"ct","m":{"4":{"intState":0,"intHP":0,"intMP":0,"targets":["lorekeeper03"]},"3":{}},"p":{"lorekeeper03":{"intMP":87,"intState":1}},"sarsa":[{"cInf":"p:122531","a":[{"type":"hit","hp":30,"actRef":"aa","tInf":"m:4"}],"actID":9,"iRes":1}],"anims":[{"strFrame":"r3","cInf":"p:122531","tInf":"m:4","animStr":"Attack1,Attack2","fx":"m"}]}}}
            JSON)->unwrap())->unwrap();

        static::assertSame(4, $event->monsters[0]->id->value);
        static::assertSame(0, $event->monsters[0]->health?->value);
        static::assertSame(CombatState::Dead, $event->monsters[0]->state);
        static::assertNull($event->monsters[1]->health);
        static::assertNull($event->monsters[1]->state);
        static::assertSame('lorekeeper03', $event->players[0]->name->value);
        static::assertSame(87, $event->players[0]->mana?->value);
        static::assertNull($event->players[0]->health);
        static::assertSame(CombatState::Idle, $event->players[0]->state);

        $empty = CombatUpdatedEvent::from(
            JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"ct"}}}')->unwrap(),
        )->unwrap();
        static::assertSame([], $empty->monsters);
        static::assertSame([], $empty->players);
    }

    #[Test]
    public function it_reads_rewards_and_level_ups(): void
    {
        $reward = GoldExpGainedEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"addGoldExp","intGold":3,"intExp":10,"iCP":510,"bonusCP":255,"typ":"m","id":4}}}',
            )->unwrap(),
        )->unwrap();
        $level = LevelUpEvent::from(
            JsonMessage::from(
                '{"t":"xt","b":{"r":-1,"o":{"cmd":"levelUp","xp":5,"intExpToLevel":100,"intLevel":2}}}',
            )->unwrap(),
        )->unwrap();

        static::assertSame(3, $reward->gold->value);
        static::assertSame(10, $reward->experience->value);
        static::assertSame(2, $level->level->value);
        static::assertSame(100, $level->toNextLevel->value);
    }

    #[Test]
    public function it_reads_deaths_resurrections_respawns_and_rejected_attacks(): void
    {
        static::assertTrue(
            PlayerDiedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"playerDeath","userID":74059365,"did":74042869,"monPad":2,"entType":"m"}}}',
                )->unwrap(),
            )->isSome(),
        );
        static::assertTrue(ResurrectedEvent::from(DelimitedMessage::from('%xt%resTimed%-1%')->unwrap())->isSome());
        static::assertSame(
            4,
            MonsterRespawnedEvent::from(DelimitedMessage::from('%xt%respawnMon%-1%4%')->unwrap())
                ->unwrap()
                ->id
                ->value,
        );
        static::assertSame(
            1,
            AttackRejectedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"sars","cInf":"p:122507","a":[],"actID":1,"iRes":0}}}',
                )->unwrap(),
            )->unwrap()->action,
        );
        static::assertTrue(
            AttackRejectedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"sars","cInf":"p:122507","a":[],"actID":1,"iRes":1}}}',
                )->unwrap(),
            )->isNone(),
        );
        static::assertTrue(ResurrectedEvent::from(DelimitedMessage::from('%xt%respawnMon%-1%4%')->unwrap())->isNone());
    }

    #[Test]
    public function it_rejects_combat_messages_it_cannot_read(): void
    {
        $this->expectException(MalformedEventException::class);

        LevelUpEvent::from(JsonMessage::from('{"b":{"o":{"cmd":"levelUp","intLevel":101}}}')->unwrap());
    }

    #[Test]
    public function it_rejects_a_respawn_without_a_monster(): void
    {
        $this->expectException(MalformedEventException::class);

        MonsterRespawnedEvent::from(DelimitedMessage::from('%xt%respawnMon%-1%x%')->unwrap());
    }
}
