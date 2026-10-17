<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\CharacterClass\Skill\Skill;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\AttackRejectedEvent;
use Lorekeeper\Server\Event\Combat\MonsterHealth;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\ResurrectedEvent;
use Lorekeeper\Server\Event\Combat\SkillsLoadedEvent;
use Lorekeeper\Server\Exception\MalformedEventException;
use Lorekeeper\Server\Message\DelimitedMessage;
use Lorekeeper\Server\Message\JsonMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CombatEventsTest extends TestCase
{
    private const string KILL = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"ct","m":{"4":{"intState":0,"intHP":0,"intMP":0,"targets":["lorekeeper03"]},"3":{}},"p":{"lorekeeper03":{"intMP":87,"intState":1}},"sarsa":[{"cInf":"p:122531","a":[{"type":"hit","hp":30,"actRef":"aa","tInf":"m:4"}],"actID":9,"iRes":1}],"anims":[{"strFrame":"r3","cInf":"p:122531","tInf":"m:4","animStr":"Attack1,Attack2","fx":"m"}]}}}
        JSON;

    private const string HIT = <<<'JSON'
        {"t":"xt","b":{"r":-1,"o":{"cmd":"ct","m":{"3":{"intHP":677}},"p":{"lorekeeper03":{"intHP":189}},"sara":[{"iRes":1,"actionResult":{"type":"hit","hp":42,"cInf":"m:1","tInf":"p:122628"}}]}}}
        JSON;

    #[Test]
    public function it_reads_the_skills_of_the_class_and_which_are_unlocked(): void
    {
        $event = SkillsLoadedEvent::from(JsonMessage::from(<<<'JSON'
            {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":89,"nam":"Auto Attack","typ":"aa","ref":"aa","tgt":"h","fx":"m","damage":0.9,"range":301,"mp":0,"cd":2000,"auto":true,"isOK":true},{"id":115,"nam":"Fireball","typ":"m","ref":"a1","tgt":"h","tgtMax":1,"mp":20,"cd":6000,"isOK":true},{"id":116,"nam":"Ice Shard","typ":"m","ref":"a2","tgt":"h","mp":15,"cd":6000,"isOK":false},{"id":118,"nam":"Arcane Shield","typ":"m","ref":"a4","tgt":"s","mp":0,"cd":20000,"isOK":false},{"nam":"Potions","typ":"i","ref":"i1","tgt":"f","mp":0,"cd":60000,"isOK":true}],"passive":[{"nam":"Hot Headed","ref":"p1"}]}}}}
            JSON)->unwrap())->unwrap();

        static::assertSame(
            [
                [SkillSlot::AutoAttack, SkillTarget::Enemy, 0,  2000],
                [SkillSlot::First,      SkillTarget::Enemy, 20, 6000],
                [SkillSlot::Second,     SkillTarget::Enemy, 15, 6000],
                [SkillSlot::Fourth,     SkillTarget::Self,  0,  20_000],
                [SkillSlot::Potion,     SkillTarget::Ally,  0,  60_000],
            ],
            array_map(static fn(Skill $skill): array => [
                $skill->slot,
                $skill->target,
                $skill->mana->value,
                (int) $skill->cooldown->getTotalMilliseconds(),
            ], $event->skills),
        );
        static::assertSame([SkillSlot::AutoAttack, SkillSlot::First, SkillSlot::Potion], $event->usable);
        static::assertSame('5 skills, 3 unlocked', $event->summary());
    }

    #[Test]
    public function a_combat_round_tells_which_monsters_died(): void
    {
        $died = MonstersDiedEvent::from(JsonMessage::from(self::KILL)->unwrap())->unwrap();

        static::assertEquals([new MonsterSpawnId(4)], $died->monsters);
        static::assertTrue(MonstersDiedEvent::from(JsonMessage::from(self::HIT)->unwrap())->isNone());
    }

    #[Test]
    public function a_combat_round_tells_whose_health_and_mana_changed(): void
    {
        $kill = JsonMessage::from(self::KILL)->unwrap();
        $hit = JsonMessage::from(self::HIT)->unwrap();

        static::assertEquals(
            [new MonsterHealth(new MonsterSpawnId(4), new Health(0))],
            MonstersHealthChangedEvent::from($kill)->unwrap()->monsters,
        );
        static::assertSame(677, MonstersHealthChangedEvent::from($hit)->unwrap()->monsters[0]->health->value);

        $mana = PlayersManaChangedEvent::from($kill)->unwrap()->players[0];
        static::assertSame('lorekeeper03', $mana->player->value);
        static::assertSame(87, $mana->mana->value);
        static::assertTrue(PlayersManaChangedEvent::from($hit)->isNone());

        static::assertSame(189, PlayersHealthChangedEvent::from($hit)->unwrap()->players[0]->health->value);
        static::assertTrue(PlayersHealthChangedEvent::from($kill)->isNone());

        static::assertSame('Monster 4 died', MonstersDiedEvent::from($kill)->unwrap()->summary());
        static::assertSame('Monster 3 has 677 health', MonstersHealthChangedEvent::from($hit)->unwrap()->summary());
        static::assertSame('lorekeeper03 has 189 health', PlayersHealthChangedEvent::from($hit)->unwrap()->summary());
        static::assertSame('lorekeeper03 has 87 mana', PlayersManaChangedEvent::from($kill)->unwrap()->summary());
        static::assertSame('MonstersHealthChanged', MonstersHealthChangedEvent::from($hit)->unwrap()->name());
    }

    #[Test]
    public function an_empty_combat_round_says_nothing(): void
    {
        $empty = JsonMessage::from('{"t":"xt","b":{"r":-1,"o":{"cmd":"ct"}}}')->unwrap();

        static::assertTrue(MonstersDiedEvent::from($empty)->isNone());
        static::assertTrue(MonstersHealthChangedEvent::from($empty)->isNone());
        static::assertTrue(PlayersHealthChangedEvent::from($empty)->isNone());
        static::assertTrue(PlayersManaChangedEvent::from($empty)->isNone());
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
        static::assertTrue(
            AttackRejectedEvent::from(
                JsonMessage::from(
                    '{"t":"xt","b":{"r":-1,"o":{"cmd":"sars","cInf":"p:122507","a":[],"actID":1,"iRes":0}}}',
                )->unwrap(),
            )->isSome(),
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
    public function it_rejects_a_respawn_without_a_monster(): void
    {
        $this->expectException(MalformedEventException::class);

        MonsterRespawnedEvent::from(DelimitedMessage::from('%xt%respawnMon%-1%x%')->unwrap());
    }

    #[Test]
    public function it_rejects_a_skill_aimed_at_something_unknown(): void
    {
        $this->expectException(MalformedEventException::class);

        SkillsLoadedEvent::from(
            JsonMessage::from(
                '{"b":{"o":{"cmd":"sAct","actions":{"active":[{"ref":"a1","tgt":"x","mp":0,"cd":1000}]}}}}',
            )->unwrap(),
        );
    }
}
