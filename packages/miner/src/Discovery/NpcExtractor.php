<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Npc\NpcName;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class NpcExtractor
{
    private const string SUFFIX = '_APOP.as';

    public static function fromSource(string $name, string $code): ?Npc
    {
        $quests = PopupQuests::in($code);
        $shops = PopupShops::in($code);

        if ($quests === [] && $shops === []) {
            return null;
        }

        return new Npc(new NpcName($name), $quests, $shops);
    }

    /** @return list<Npc> */
    public static function fromDirectory(string $directory): array
    {
        $npcs = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if (!str_ends_with($file->getFilename(), self::SUFFIX)) {
                continue;
            }

            $npc = self::fromSource(
                substr($file->getFilename(), offset: 0, length: -strlen(self::SUFFIX)),
                (string) file_get_contents($file->getPathname()),
            );
            if ($npc !== null) {
                $npcs[] = $npc;
            }
        }

        usort($npcs, static fn(Npc $a, Npc $b): int => strcmp($a->name->value, $b->name->value));

        return $npcs;
    }
}
