<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

use Lorekeeper\Core\World\Map\ExitPrompt;
use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Miner\Discovery\MapNameExtractor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MapNameExtractorTest extends TestCase
{
    #[Test]
    public function it_extracts_map_names_from_known_patterns(): void
    {
        $code = <<<'AS'
            MovieClip(root).world.gotoTown("Yulgar","Enter","Spawn");
            this.strNewMap = "greenguardeast";
            btnStory.strString = "battleontown";
            btnStory2.strString = "Story";
            btnBroken.strString = "not a map";
            this.strNewMap = "greenguardeast";
            this.__id50_.strMap = "battleon";
            AS;

        static::assertEquals(
            [
                new MapName('yulgar'),
                new MapName('greenguardeast'),
                new MapName('battleontown'),
                new MapName('story'),
                new MapName('battleon'),
            ],
            MapNameExtractor::mentionsIn($code),
        );
    }

    #[Test]
    public function it_only_counts_walk_arrows_as_exits(): void
    {
        $code = <<<'AS'
            this.__id2_.strNewMap = "SwordhavenUndead";
            this.__id2_.strSpawnCell = "Gates";
            this.__id60_.strString = "shadowfall";
            this.__id50_.strMap = "battleon";
            AS;

        static::assertEquals([new MapExit(new MapName('swordhavenundead'))], MapNameExtractor::exitsIn($code));
    }

    #[Test]
    public function it_keeps_the_question_each_arrow_asks(): void
    {
        $code = <<<'AS'
            internal function __setProp___id21__MainTimeline_Pads_0(param1:int) : *
            {
               this.__id21_.strNewMap = "librarium";
               this.__id21_.strAsk = "Enter the Librarium?";
            }
            internal function __setProp___id25__MainTimeline_Pads_0(param1:int) : *
            {
               this.__id25_.strNewMap = "greenguardwest";
               this.__id25_.strAsk = "";
            }
            AS;

        static::assertEquals(
            [
                new MapExit(new MapName('librarium'), new ExitPrompt('Enter the Librarium?')),
                new MapExit(new MapName('greenguardwest')),
            ],
            MapNameExtractor::exitsIn($code),
        );
    }

    #[Test]
    public function it_reads_every_actionscript_file_in_a_directory(): void
    {
        $directory = sys_get_temp_dir() . '/lorekeeper-' . bin2hex(random_bytes(4));
        mkdir("{$directory}/scripts/sub", recursive: true);
        file_put_contents("{$directory}/scripts/a.as", data: 'this.strNewMap = "fableforest";');
        file_put_contents("{$directory}/scripts/sub/b.as", data: 'gotoTown("greenguardeast");');
        file_put_contents("{$directory}/scripts/ignored.txt", data: 'gotoTown("nope");');

        $found = MapNameExtractor::fromDirectory($directory);
        $names = array_map(static fn(MapName $name): string => $name->value, $found['mentions']);
        sort($names);

        static::assertSame(['fableforest', 'greenguardeast'], $names);
        static::assertEquals([new MapExit(new MapName('fableforest'))], $found['exits']);

        array_map(unlink(...), [
            "{$directory}/scripts/a.as",
            "{$directory}/scripts/sub/b.as",
            "{$directory}/scripts/ignored.txt",
        ]);
        rmdir("{$directory}/scripts/sub");
        rmdir("{$directory}/scripts");
        rmdir($directory);
    }
}
