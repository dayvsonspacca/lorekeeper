<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Miner\Discovery\Npc;
use Lorekeeper\Miner\Discovery\NpcExtractor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NpcExtractorTest extends TestCase
{
    private const string TWILLY = <<<'AS'
        internal function __setProp___id122__Twilly_APOP_Content_0(param1:int) : *
        {
           this.__id122_.strAction = "GotoAndStop";
           this.__id122_.strFrame = "Talk1";
           this.__id122_.intID = 1;
        }
        internal function __setProp___id123__Twilly_APOP_Content_0(param1:int) : *
        {
           this.__id123_.strQuests = "11,407,408";
           this.__id123_.strTurnIns = "11,407,408";
        }
        internal function __setProp___id124__Twilly_APOP_Content_0(param1:int) : *
        {
           this.__id124_.strAction = "Item Shop";
           this.__id124_.strString = "";
           this.__id124_.intID = 31;
        }
        internal function __setProp___id125__Twilly_APOP_Content_0(param1:int) : *
        {
           this.__id125_.strAction = "/Join";
           this.__id125_.strString = "oaklore";
           this.__id125_.intID = 31;
        }
        internal function __setProp___id126__Twilly_APOP_Content_0(param1:int) : *
        {
           this.__id126_.strQuests = "408,409";
        }
        AS;

    #[Test]
    public function it_reads_quests_and_shops_of_an_npc(): void
    {
        static::assertEquals(
            new Npc(
                new NpcName('Twilly'),
                [new QuestId(11), new QuestId(407), new QuestId(408), new QuestId(409)],
                [new ShopId(31)],
            ),
            NpcExtractor::fromSource('Twilly', self::TWILLY),
        );
    }

    #[Test]
    public function it_ignores_npcs_without_quests_or_shops(): void
    {
        static::assertNull(NpcExtractor::fromSource('Talker', 'this.__id1_.strAction = "GotoAndStop";'));
    }

    #[Test]
    public function it_reads_npc_popups_from_a_directory(): void
    {
        $directory = sys_get_temp_dir() . '/lorekeeper-' . bin2hex(random_bytes(4));
        mkdir("{$directory}/scripts", recursive: true);
        file_put_contents("{$directory}/scripts/Twilly_APOP.as", data: self::TWILLY);
        file_put_contents("{$directory}/scripts/MainTimeline.as", data: 'this.strQuests = "1";');

        $npcs = NpcExtractor::fromDirectory($directory);

        static::assertSame(['Twilly'], array_map(static fn(Npc $npc): string => $npc->name->value, $npcs));

        unlink("{$directory}/scripts/Twilly_APOP.as");
        unlink("{$directory}/scripts/MainTimeline.as");
        rmdir("{$directory}/scripts");
        rmdir($directory);
    }
}
