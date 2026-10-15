<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcTitle;

final readonly class PopupTexts
{
    private const int LONGEST_NAME = 40;

    private const int LONGEST_TITLE = 60;

    private const array PLACEHOLDERS = ['DudeName', 'DudeTitle'];

    private function __construct(
        private SwfStructure $structure,
    ) {}

    public static function read(string $xmlFile, string $textDirectory): self
    {
        return new self(SwfStructure::read($xmlFile, $textDirectory));
    }

    public function of(string $linkage): PopupText
    {
        $lines = array_values(array_filter(
            $this->structure->textsOf($linkage),
            static fn(array $line): bool => mb_strlen($line['text']) > 1,
        ));
        if ($lines === []) {
            return new PopupText(null, null);
        }

        $tallest = max(array_column($lines, 'height'));
        $nameAt = (int) array_search($tallest, array_column($lines, 'height'), strict: true);
        $name = $lines[$nameAt]['text'];

        $siblings = array_values(array_filter(
            array_slice($lines, $nameAt + 1),
            static fn(array $line): bool => $line['parent'] === $lines[$nameAt]['parent'],
        ));
        $title = $siblings[0]['text'] ?? null;

        return new PopupText(
            self::fits($name, self::LONGEST_NAME) ? new NpcName($name) : null,
            $title !== null && $title !== $name && self::fits($title, self::LONGEST_TITLE)
                ? new NpcTitle($title)
                : null,
        );
    }

    private static function fits(string $text, int $longest): bool
    {
        return $text !== '' && mb_strlen($text) <= $longest && !in_array($text, self::PLACEHOLDERS, strict: true);
    }
}
