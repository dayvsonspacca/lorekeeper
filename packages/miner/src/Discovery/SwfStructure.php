<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use DOMElement;

final class SwfStructure
{
    /** @var array<string, int> */
    private array $symbols = [];

    /** @var array<int, list<int>> */
    private array $children = [];

    /** @var array<int, array{height: int, text: string}> */
    private array $texts = [];

    private function __construct(
        private readonly string $textDirectory,
    ) {}

    public static function read(string $xmlFile, string $textDirectory): self
    {
        $structure = new self($textDirectory);
        foreach (SwfXml::nodes($xmlFile) as [$type, $node]) {
            $structure->remember($type, $node);
        }

        return $structure;
    }

    /** @return list<array{height: int, text: string, parent: int}> */
    public function textsOf(string $symbol): array
    {
        $id = $this->symbols[$symbol] ?? null;
        if ($id === null) {
            return [];
        }

        $texts = [];
        foreach ($this->collect($id, []) as $text => $parent) {
            $texts[] = [...$this->texts[$text], 'parent' => $parent];
        }

        return $texts;
    }

    /**
     * @param array<int, int> $found
     *
     * @return array<int, int>
     */
    private function collect(int $id, array $found): array
    {
        foreach ($this->children[$id] ?? [] as $child) {
            if (array_key_exists($child, $found)) {
                continue;
            }

            if (array_key_exists($child, $this->texts)) {
                $found[$child] = $id;

                continue;
            }

            $found = $this->collect($child, $found);
        }

        return $found;
    }

    private function remember(string $type, DOMElement $node): void
    {
        match ($type) {
            'SymbolClassTag' => $this->symbols = [...$this->symbols, ...SwfTags::symbols($node)],
            'DefineSpriteTag' => $this->children[(int) $node->getAttribute('spriteId')] = SwfTags::children($node),
            'DefineEditTextTag' => $this->texts[(int) $node->getAttribute('characterID')] = SwfTags::editText($node),
            default => $this->texts[(int) $node->getAttribute('characterID')] = SwfTags::text(
                $node,
                $this->textDirectory,
            ),
        };
    }
}
