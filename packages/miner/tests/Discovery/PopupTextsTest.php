<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcTitle;
use Lorekeeper\Miner\Discovery\PopupText;
use Lorekeeper\Miner\Discovery\PopupTexts;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PopupTextsTest extends TestCase
{
    public static function xml(): string
    {
        return <<<'XML'
            <swf>
              <tags>
                <item type="DefineTextTag" characterID="10"><textRecords><item type="TEXTRECORD" textHeight="800"/></textRecords></item>
                <item type="DefineTextTag" characterID="11"><textRecords><item type="TEXTRECORD" textHeight="320"/></textRecords></item>
                <item type="DefineTextTag" characterID="12"><textRecords><item type="TEXTRECORD" textHeight="280"/></textRecords></item>
                <item type="DefineEditTextTag" characterID="13" fontHeight="900" initialText="DudeName"/>
                <item type="DefineTextTag" characterID="14"><textRecords><item type="TEXTRECORD" textHeight="500"/></textRecords></item>
                <item type="DefineSpriteTag" spriteId="23">
                  <subTags><item type="PlaceObject2Tag" characterId="14"/></subTags>
                </item>
                <item type="DefineSpriteTag" spriteId="20">
                  <subTags>
                    <item type="PlaceObject2Tag" characterId="10"/>
                    <item type="PlaceObject2Tag" characterId="23"/>
                    <item type="PlaceObject2Tag" characterId="11"/>
                    <item type="PlaceObject2Tag" characterId="12"/>
                  </subTags>
                </item>
                <item type="DefineSpriteTag" spriteId="21">
                  <subTags><item type="PlaceObject2Tag" characterId="20"/></subTags>
                </item>
                <item type="DefineSpriteTag" spriteId="22">
                  <subTags><item type="PlaceObject2Tag" characterId="13"/></subTags>
                </item>
                <item type="SymbolClassTag">
                  <tags><item>21</item><item>22</item></tags>
                  <names><item>Guard1_APOP</item><item>Default_APOP</item></names>
                </item>
              </tags>
            </swf>
            XML;
    }

    #[Test]
    public function it_reads_the_tallest_text_as_name_and_the_next_sibling_as_title(): void
    {
        static::assertEquals(
            new PopupText(new NpcName('Sir Render'), new NpcTitle('Knight of the Pactagonal Table')),
            self::texts()->of('Guard1_APOP'),
        );
    }

    #[Test]
    public function it_ignores_template_placeholders_and_unknown_popups(): void
    {
        static::assertEquals(new PopupText(null, null), self::texts()->of('Default_APOP'));
        static::assertEquals(new PopupText(null, null), self::texts()->of('Red_APOP'));
    }

    private static function texts(): PopupTexts
    {
        $directory = sys_get_temp_dir() . '/lorekeeper-' . bin2hex(random_bytes(4));
        mkdir($directory);
        file_put_contents("{$directory}/map.xml", data: self::xml());
        file_put_contents("{$directory}/10.txt", data: 'Sir Render');
        file_put_contents("{$directory}/11.txt", data: "Knight of the\nPactagonal Table");
        file_put_contents("{$directory}/12.txt", data: "We cannot even surrender!\n--- RECORDSEPARATOR\nAgain");
        file_put_contents("{$directory}/14.txt", data: 'Back');

        $texts = PopupTexts::read("{$directory}/map.xml", $directory);

        foreach (['map.xml', '10.txt', '11.txt', '12.txt', '14.txt'] as $file) {
            unlink("{$directory}/{$file}");
        }

        rmdir($directory);

        return $texts;
    }
}
