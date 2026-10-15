<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Asset\Linkage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class NpcExtractor
{
    private const string SUFFIX = '_APOP';

    public static function fromSource(string $popup, string $code, ?PopupText $text = null): ?Npc
    {
        $buttons = PopupButtons::in($code);
        if ($buttons->isEmpty() && $text?->name === null) {
            return null;
        }

        return new Npc(new Linkage($popup), $buttons, $text ?? new PopupText(null, null));
    }

    /** @return list<string> */
    public static function placements(string $code): array
    {
        $matches = [];
        preg_match_all('/\.(?:apop|cnt)Linkage\s*=\s*"(\w+)"/', $code, $matches);

        return array_values(array_unique($matches[1]));
    }

    /** @return list<Npc> */
    public static function fromDirectory(string $directory): array
    {
        $sources = [];
        $placed = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->getExtension() !== 'as') {
                continue;
            }

            $code = (string) file_get_contents($file->getPathname());
            $sources[$file->getBasename('.as')] = $code;
            foreach (self::placements($code) as $linkage) {
                $placed[$linkage] = true;
            }
        }

        foreach (array_keys($sources) as $class) {
            if (!str_ends_with($class, self::SUFFIX)) {
                continue;
            }

            $placed[$class] ??= false;
        }

        $texts = PopupTexts::read("{$directory}/map.xml", "{$directory}/texts");
        $npcs = [];
        foreach ($placed as $linkage => $onMap) {
            $text = $texts->of($linkage);
            if ($text->name === null && (!$onMap || !array_key_exists($linkage, $sources))) {
                continue;
            }

            $npc = self::fromSource($linkage, $sources[$linkage] ?? '', $text);
            if ($npc !== null) {
                $npcs[] = $npc;
            }
        }

        usort($npcs, static fn(Npc $a, Npc $b): int => strcmp($a->popup->value, $b->popup->value));

        return $npcs;
    }
}
