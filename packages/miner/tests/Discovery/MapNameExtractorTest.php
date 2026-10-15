<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Tests\Discovery;

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
            AS;

        static::assertEquals(
            [new MapName('yulgar'), new MapName('greenguardeast'), new MapName('battleontown'), new MapName('story')],
            MapNameExtractor::fromSource($code),
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

        $names = array_map(
            static fn(MapName $name): string => $name->value,
            MapNameExtractor::fromDirectory($directory),
        );
        sort($names);

        static::assertSame(['fableforest', 'greenguardeast'], $names);

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
