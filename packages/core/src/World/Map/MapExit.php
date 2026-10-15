<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

final readonly class MapExit
{
    public function __construct(
        public MapName $mapName,
        public ?ExitPrompt $prompt = null,
    ) {}
}
