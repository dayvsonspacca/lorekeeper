<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\Passive\ClassPassive;
use Lorekeeper\Core\CharacterClass\Passive\PassiveDescription;
use Lorekeeper\Core\CharacterClass\Passive\PassiveName;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaCost;
use Lorekeeper\Core\CharacterClass\Skill\Cost\ManaRestore;
use Lorekeeper\Core\CharacterClass\Skill\Cost\SkillCost;
use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageMultiplier;
use Lorekeeper\Core\CharacterClass\Skill\Damage\DamageSource;
use Lorekeeper\Core\CharacterClass\Skill\Damage\SkillDamage;
use Lorekeeper\Core\CharacterClass\Skill\SkillSlot;
use Lorekeeper\Core\CharacterClass\Skill\SkillTarget;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Player\PlayerSkill;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\AttackRejectedEvent;
use Lorekeeper\Server\Event\Combat\MonsterHealth;
use Lorekeeper\Server\Event\Combat\MonsterRespawnedEvent;
use Lorekeeper\Server\Event\Combat\MonstersDiedEvent;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Combat\PlayersHealthChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersManaChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayersStateChangedEvent;
use Lorekeeper\Server\Event\Combat\PlayerState;
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
            {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":89,"nam":"Auto Attack","typ":"aa","ref":"aa","tgt":"h","fx":"m","damage":0.9,"range":301,"mp":0,"cd":2000,"auto":true,"isOK":true},{"id":115,"nam":"Fireball","typ":"m","ref":"a1","tgt":"h","tgtMax":1,"mp":20,"cd":6000,"isOK":true},{"id":116,"nam":"Ice Shard","typ":"m","ref":"a2","tgt":"h","mp":15,"cd":6000,"isOK":false},{"id":118,"nam":"Arcane Shield","typ":"m","ref":"a4","tgt":"s","mp":0,"cd":20000,"isOK":false},{"nam":"Potions","typ":"i","ref":"i1","tgt":"f","mp":0,"cd":60000,"isOK":true}]}}}}
            JSON)->unwrap())->unwrap();

        static::assertEquals(
            [
                [SkillSlot::AutoAttack, SkillTarget::Enemy, new ManaCost(new Mana(0)), 2000],
                [SkillSlot::First, SkillTarget::Enemy, new ManaCost(new Mana(20)), 6000],
                [SkillSlot::Second, SkillTarget::Enemy, new ManaCost(new Mana(15)), 6000],
                [SkillSlot::Fourth, SkillTarget::Self, new ManaCost(new Mana(0)), 20_000],
                [SkillSlot::Potion, SkillTarget::Ally, new ManaCost(new Mana(0)), 60_000],
            ],
            array_map(static fn(PlayerSkill $held): array => [
                $held->skill->slot,
                $held->skill->target,
                $held->skill->cost,
                (int) $held->skill->cooldown->getTotalMilliseconds(),
            ], $event->skills),
        );
        static::assertSame(
            [true, true, false, false, true],
            array_map(static fn(PlayerSkill $held): bool => $held->unlocked, $event->skills),
        );
        static::assertSame('5 skills, 3 unlocked', $event->summary());
    }

    #[Test]
    public function a_skill_that_gives_mana_back_restores_it_instead_of_costing_it(): void
    {
        $event = SkillsLoadedEvent::from(JsonMessage::from(<<<'JSON'
            {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":638,"nam":"Arcane Strike","typ":"aa","ref":"aa","tgt":"h","mp":-10,"cd":2000,"isOK":true},{"id":642,"nam":"Arcane Devastation","typ":"m","ref":"a1","tgt":"h","tgtMax":3,"mp":50,"cd":10000,"isOK":true},{"id":639,"nam":"Arcane Fire","typ":"m","ref":"a2","tgt":"h","tgtMax":3,"mp":-15,"cd":7000,"isOK":true},{"id":641,"nam":"Explosive Shield","typ":"m","ref":"a3","tgt":"h","tgtMax":3,"mp":20,"cd":23000,"isOK":true},{"id":640,"nam":"Enchanted Blade","typ":"m","ref":"a4","tgt":"s","mp":40,"cd":4000,"isOK":true},{"nam":"Potions","typ":"i","ref":"i1","tgt":"f","mp":0,"cd":60000,"isOK":true}]}}}}
            JSON)->unwrap())->unwrap();

        static::assertEquals(
            [
                new ManaRestore(new Mana(10)),
                new ManaCost(new Mana(50)),
                new ManaRestore(new Mana(15)),
                new ManaCost(new Mana(20)),
                new ManaCost(new Mana(40)),
                new ManaCost(new Mana(0)),
            ],
            array_map(static fn(PlayerSkill $held): SkillCost => $held->skill->cost, $event->skills),
        );
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
        static::assertEquals(
            [new PlayerState(new PlayerName('lorekeeper03'), CombatState::Idle)],
            PlayersStateChangedEvent::from($kill)->unwrap()->players,
        );
        static::assertTrue(PlayersStateChangedEvent::from($hit)->isNone());
        static::assertSame('lorekeeper03 is idle', PlayersStateChangedEvent::from($kill)->unwrap()->summary());
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
    public function it_reads_how_many_targets_each_skill_of_a_real_class_hits(): void
    {
        $event = SkillsLoadedEvent::from(JsonMessage::from(<<<'JSON'
            {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":1354,"nam":"Fire Bolt","typ":"aa","ref":"aa","desc":"Blast your opponents at long range with fire bolts.","tgt":"h","tgtMax":3,"fx":"m","damage":1,"dsrc":"AP2","range":808,"mp":0,"cd":2000,"anim":"Cast1","icon":"iwstaff","tgtMin":1,"auto":true,"isOK":true},{"id":848,"nam":"Combustion Blaze","typ":"m","ref":"a1","desc":"Unleash explosive flames on three enemies and then watch them burn with a small DoT for 4 seconds afterwards.","tgt":"h","tgtMax":3,"fx":"m","strl":"sp_ed1","damage":0.65,"dsrc":"SP1","range":808,"mp":10,"cd":4000,"anim":"Castgood","icon":"iel2,ief1","auras":[{}],"tgtMin":1,"forceResult":"crit","isOK":true},{"id":849,"nam":"Choking Shadow Smoke","typ":"m","ref":"a2","desc":"Your smoke is infused with the power of Darkness and causes your opponents to have a reduced chance of hitting you and occasionally stuns them for 3 seconds. Inhaling the smoke also causes a large DoT lasting for 8 seconds.","tgt":"h","tgtMax":3,"fx":"m","strl":"sp_ed1","range":808,"mp":25,"cd":8000,"icon":"imp1,ied2","auras":[{},{}],"tgtMin":1,"isOK":true},{"id":850,"nam":"Fire in Your Skin","typ":"m","ref":"a3","desc":"Kindle a flame within you, setting yourself ablaze. Applies Burning Ward which reduces damage taken by 40% while damaging opponents that attack you for 6 seconds, and Blazing Speed which increases haste by 15% for 14 seconds. You also use your darkness powers to restore your life from the ashes around you to apply an HoT for 8 seconds.","tgt":"s","tgtMax":1,"fx":"m","strl":"sp_ef4","range":301,"mp":30,"cd":16000,"anim":"Attack3","icon":"ipn1","auras":[{},{},{}],"tgtMin":1,"isOK":true},{"id":851,"nam":"Dark Fire","typ":"m","ref":"a4","desc":"You unleash a devastating burst of Dark Fire on 3 opponents. They all take heavy damage and continue to burn from a reasonable DoT for 8 seconds. The fire will also apply either a 10% DoT damage taken debuff or a -10% damage debuff to your enemy.","tgt":"h","tgtMax":3,"fx":"m","damage":3.5,"dsrc":"SP2","range":808,"mp":25,"cd":12000,"anim":"Castgood","icon":"ief2,isi1","auras":[{},{},{}],"tgtMin":1,"forceResult":"hit","randomAura":true,"randomLength":1,"Apply":2,"isOK":true},{"nam":"Potions","ref":"i1","desc":"Equip a potion or scroll from your inventory to use it here.","typ":"i","fx":"","strl":"","tgt":"f","range":808,"mp":0,"cd":60000,"anim":"Cheer","icon":"icu1","isOK":true}],"passive":[{"id":852,"nam":"Sear Flesh","typ":"passive","ref":"p1","desc":"20% Damage to DoTs","tgt":"s","fx":"m","range":301,"icon":"isp2","auras":[{}],"cd":60000,"isOK":true},{"id":853,"nam":"Sear Bone","typ":"passive","ref":"p2","desc":"10% Outgoing Damage","tgt":"s","fx":"m","range":301,"icon":"isp2","auras":[{}],"cd":60000,"isOK":true},{"id":476,"nam":"Burn At Both Ends","typ":"passive","ref":"p3","desc":"Occasionally, your Haste and Mana Regen will GREATLY increase for a short time.","tgt":"s","fx":"m","range":301,"icon":"isp2","auras":[{}],"pRule":"Aura200","auraID":830,"cd":60000,"isOK":true}]}}}}
            JSON)->unwrap())->unwrap();

        static::assertSame(
            [3, 3, 3, 1, 3, 1],
            array_map(static fn(PlayerSkill $held): int => $held->skill->targets->value, $event->skills),
        );
        static::assertSame(
            [true, true, true, true, true, true],
            array_map(static fn(PlayerSkill $held): bool => $held->unlocked, $event->skills),
        );
    }

    #[Test]
    public function it_reads_what_each_skill_is_called_how_hard_it_hits_and_the_class_passives(): void
    {
        $event = SkillsLoadedEvent::from(JsonMessage::from(<<<'JSON'
            {"t":"xt","b":{"r":-1,"o":{"cmd":"sAct","actions":{"active":[{"id":1558,"nam":"Malevolent Burst","typ":"aa","ref":"aa","desc":"A sinister spell of powerful dark energy. Recovers 15 mana on hit.","tgt":"h","tgtMax":3,"damage":0.57,"dsrc":"SP1","mp":-15,"cd":1500,"isOK":true},{"id":1561,"nam":"Depraved Empowerment","typ":"m","ref":"a3","desc":"Touch the darkness within yourself and your allies by drawing out their twisted inclinations. Applies Depravity, increasing you and your allies' Dodge, Crit Chance, and Outgoing Damage by 30%. Immorality strengthens your magic, increasing your Haste by 20%, Crit Damage by 30%, and healing you over time. Also applies Arcane Shield, reducing non True Damage taken by 30% of your Spell Power and draining 8 mana when hit. Effects last 12 seconds.","tgt":"f","tgtMax":7,"mp":15,"cd":6000,"isOK":true}],"passive":[{"id":1564,"nam":"Dark Scholar","typ":"passive","ref":"p2","desc":"Increases outgoing magic damage by 40%"}]}}}}
            JSON)->unwrap())->unwrap();

        $burst = $event->skills[0]->skill;
        $empowerment = $event->skills[1]->skill;
        static::assertSame('Malevolent Burst', $burst->name->value);
        static::assertSame(
            'A sinister spell of powerful dark energy. Recovers 15 mana on hit.',
            $burst->description->unwrap()->value,
        );
        static::assertEquals(
            new SkillDamage(new DamageMultiplier(0.57), DamageSource::Magical),
            $burst->damage->unwrap(),
        );
        static::assertSame('Depraved Empowerment', $empowerment->name->value);
        static::assertTrue($empowerment->damage->isNone());
        static::assertEquals(
            [new ClassPassive(
                new PassiveName('Dark Scholar'),
                new PassiveDescription('Increases outgoing magic damage by 40%'),
            )],
            $event->passives,
        );
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
