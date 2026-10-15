<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcTitle;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Miner\Discovery\Npc;
use Lorekeeper\Miner\Discovery\NpcExtractor;
use Lorekeeper\Miner\Discovery\PopupButtons;
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
                new Linkage('Twilly_APOP'),
                new PopupButtons(
                    [new QuestId(11), new QuestId(407), new QuestId(408), new QuestId(409)],
                    [new ShopId(31)],
                    [new MapName('oaklore')],
                ),
            ),
            NpcExtractor::fromSource('Twilly_APOP', self::TWILLY),
        );
    }

    #[Test]
    public function it_prefers_the_quest_list_the_popup_opens_on_click(): void
    {
        $code = <<<'AS'
            public function onClick(param1:MouseEvent) : void
            {
               switch(param1.currentTarget.name)
               {
                  case "ItemShop":
                     rootClass.world.sendLoadShopRequest(445);
                     break;
                  case "QuestBtn":
                     rootClass.world.showQuestList("q","2188,2189","2188,2189");
                     break;
                  case "Extra":
                     rootClass.world.showQuestList("q","3000","3000");
               }
            }
            internal function __setProp_QuestBtn_Lowell_APOP_Content_17() : *
            {
               QuestBtn.strQuests = "2044,2083";
            }
            internal function __setProp___id58__Lowell_APOP_Content_17() : *
            {
               __id58_.strQuests = "2500";
            }
            AS;

        static::assertEquals(
            new Npc(
                new Linkage('Lowell_APOP'),
                new PopupButtons([
                    new QuestId(2188),
                    new QuestId(2189),
                    new QuestId(3000),
                    new QuestId(2500),
                ], [new ShopId(445)]),
            ),
            NpcExtractor::fromSource('Lowell_APOP', $code),
        );
    }

    #[Test]
    public function it_reads_where_an_npc_can_send_you(): void
    {
        $code = <<<'AS'
            internal function __setProp___id60__Alteon_APOP_Content_0() : *
            {
               this.__id60_.strAction = "/Join";
               this.__id60_.strString = "shadowfall";
            }
            internal function __setProp___id70__Maya_APOP_Content_0() : *
            {
               this.__id70_.strCutLink = "Swordhaven/KingScene2.swf";
               this.__id70_.strMap = "battleon";
            }
            case "JoinBtn":
               rootClass.world.gotoTown("necrocavern","r16","Down");
            AS;

        static::assertEquals(
            [new MapName('shadowfall'), new MapName('necrocavern')],
            NpcExtractor::fromSource('Alteon_APOP', $code)?->buttons->travels,
        );
    }

    #[Test]
    public function it_ignores_npcs_without_quests_or_shops(): void
    {
        static::assertNull(NpcExtractor::fromSource('Talker', 'this.__id1_.strAction = "GotoAndStop";'));
    }

    #[Test]
    public function it_finds_npcs_placed_on_the_map_with_their_popup_names(): void
    {
        $directory = sys_get_temp_dir() . '/lorekeeper-' . bin2hex(random_bytes(4));
        mkdir("{$directory}/scripts", recursive: true);
        mkdir("{$directory}/texts");
        file_put_contents("{$directory}/scripts/Twilly_APOP.as", data: self::TWILLY);
        file_put_contents("{$directory}/scripts/Default_APOP.as", data: 'this.__id1_.strQuests = "2902";');
        file_put_contents("{$directory}/scripts/MainTimeline.as", data: <<<'AS'
            this.__id2_.npcLinkage = "Twilly_NPC";
            this.__id2_.apopLinkage = "Twilly_APOP";
            this.__id26_.npcLinkage = "Guard_NPC";
            this.__id26_.apopLinkage = "Guard1_APOP";
            AS);
        file_put_contents("{$directory}/map.xml", data: PopupTextsTest::xml());
        file_put_contents("{$directory}/texts/10.txt", data: 'Sir Render');
        file_put_contents("{$directory}/texts/11.txt", data: 'Knight of the Pactagonal Table');
        file_put_contents("{$directory}/texts/12.txt", data: 'We cannot even surrender!');

        $npcs = NpcExtractor::fromDirectory($directory);

        static::assertSame(
            ['Guard1_APOP', 'Twilly_APOP'],
            array_map(static fn(Npc $npc): string => $npc->popup->value, $npcs),
        );
        static::assertEquals(new NpcName('Sir Render'), $npcs[0]->text->name);
        static::assertEquals(new NpcTitle('Knight of the Pactagonal Table'), $npcs[0]->text->title);
        static::assertSame([], $npcs[0]->buttons->quests);

        foreach ([
            'scripts/Twilly_APOP.as',
            'scripts/Default_APOP.as',
            'scripts/MainTimeline.as',
            'map.xml',
            'texts/10.txt',
            'texts/11.txt',
            'texts/12.txt',
        ] as $file) {
            unlink("{$directory}/{$file}");
        }

        rmdir("{$directory}/scripts");
        rmdir("{$directory}/texts");
        rmdir($directory);
    }
}
