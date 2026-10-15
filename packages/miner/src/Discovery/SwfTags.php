<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use DOMElement;

final readonly class SwfTags
{
    /** @return array<string, int> */
    public static function symbols(DOMElement $node): array
    {
        $ids = self::listed($node, 'tags');
        $symbols = [];
        foreach (self::listed($node, 'names') as $index => $name) {
            if (!array_key_exists($index, $ids)) {
                continue;
            }

            $symbols[$name] = (int) $ids[$index];
        }

        return $symbols;
    }

    /** @return list<int> */
    public static function children(DOMElement $node): array
    {
        $children = [];
        foreach ($node->getElementsByTagName('item') as $item) {
            $character = $item->getAttribute('characterId');
            if (!str_starts_with($item->getAttribute('type'), 'PlaceObject') || $character === '') {
                continue;
            }

            $children[(int) $character] = true;
        }

        return array_keys($children);
    }

    /** @return array{height: int, text: string} */
    public static function text(DOMElement $node, string $textDirectory): array
    {
        $height = 0;
        foreach ($node->getElementsByTagName('item') as $record) {
            $height = max($height, (int) $record->getAttribute('textHeight'));
        }

        $file = $textDirectory . '/' . $node->getAttribute('characterID') . '.txt';

        return [
            'height' => $height,
            'text' => self::firstRecord(is_file($file) ? (string) file_get_contents($file) : ''),
        ];
    }

    /** @return array{height: int, text: string} */
    public static function editText(DOMElement $node): array
    {
        return [
            'height' => (int) $node->getAttribute('fontHeight'),
            'text' => self::firstRecord(html_entity_decode(strip_tags($node->getAttribute('initialText')))),
        ];
    }

    /** @return list<string> */
    private static function listed(DOMElement $node, string $list): array
    {
        $values = [];
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement || $child->nodeName !== $list) {
                continue;
            }

            foreach ($child->getElementsByTagName('item') as $item) {
                $values[] = $item->textContent;
            }
        }

        return $values;
    }

    private static function firstRecord(string $raw): string
    {
        $record = explode('--- RECORDSEPARATOR', $raw)[0];

        return trim((string) preg_replace('/\s+/', replacement: ' ', subject: $record));
    }
}
