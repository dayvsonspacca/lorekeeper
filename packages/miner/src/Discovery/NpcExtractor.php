<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class NpcExtractor
{
    private const string SUFFIX = '_APOP.as';

    public static function fromSource(string $name, string $code): ?Npc
    {
        $quests = [];
        $matches = [];
        preg_match_all('/strQuests\s*=\s*"([^"]*)"/', $code, $matches);
        foreach ($matches[1] as $list) {
            foreach (explode(',', $list) as $id) {
                $id = (int) trim($id);
                if ($id < 1) {
                    continue;
                }

                $quests[$id] = new QuestId($id);
            }
        }

        $shops = [];
        $blocks = preg_split('/internal function __setProp_/', $code);
        foreach ($blocks === false ? [] : $blocks as $block) {
            $shop = self::shopIn($block);
            if ($shop !== null) {
                $shops[$shop->value] = $shop;
            }
        }

        if ($quests === [] && $shops === []) {
            return null;
        }

        return new Npc(new NpcName($name), array_values($quests), array_values($shops));
    }

    private static function shopIn(string $block): ?ShopId
    {
        if (!str_contains($block, 'strAction = "Item Shop"')) {
            return null;
        }

        $match = [];
        preg_match('/intID\s*=\s*(\d+)/', $block, $match);
        $id = (int) ($match[1] ?? 0);

        return $id > 0 ? new ShopId($id) : null;
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
