<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Quest\QuestId;

final readonly class PopupQuests
{
    /** @return list<QuestId> */
    public static function in(string $code): array
    {
        $handled = self::handled($code);

        $quests = [];
        foreach ($handled as $list) {
            $quests += self::ids($list);
        }

        $matches = [];
        preg_match_all('/(?:this\.)?(\w+)\.strQuests\s*=\s*"([^"]*)"/', $code, $matches, PREG_SET_ORDER);
        foreach ($matches as [, $button, $list]) {
            if (array_key_exists($button, $handled)) {
                continue;
            }

            $quests += self::ids($list);
        }

        return array_values($quests);
    }

    /** @return array<string, string> */
    private static function handled(string $code): array
    {
        $calls = [];
        preg_match_all(
            '/(?:case\s+"(\w+)"\s*:(?:(?!break;|case\s)[\s\S])*?)?showQuestList\(\s*"q"\s*,\s*"([^"]*)"/',
            $code,
            $calls,
            PREG_SET_ORDER,
        );

        $handled = [];
        foreach ($calls as $position => [, $button, $list]) {
            $handled[$button !== '' ? $button : "#{$position}"] = $list;
        }

        return $handled;
    }

    /** @return array<int, QuestId> */
    private static function ids(string $list): array
    {
        $ids = [];
        foreach (explode(',', $list) as $id) {
            $id = (int) trim($id);
            if ($id < 1) {
                continue;
            }

            $ids[$id] = new QuestId($id);
        }

        return $ids;
    }
}
