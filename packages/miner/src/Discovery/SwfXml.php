<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use DOMElement;
use Generator;
use XMLReader;

final readonly class SwfXml
{
    private const array WANTED = [
        'SymbolClassTag',
        'DefineSpriteTag',
        'DefineTextTag',
        'DefineText2Tag',
        'DefineEditTextTag',
    ];

    /** @return Generator<int, array{string, DOMElement}> */
    public static function nodes(string $xmlFile): Generator
    {
        $reader = new XMLReader();
        if (!is_file($xmlFile) || !$reader->open($xmlFile)) {
            return;
        }

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->depth !== 2) {
                continue;
            }

            $type = (string) $reader->getAttribute('type');
            if (!in_array($type, self::WANTED, strict: true)) {
                continue;
            }

            $node = $reader->expand();
            if ($node instanceof DOMElement) {
                yield [$type, $node];
            }
        }

        $reader->close();
    }
}
