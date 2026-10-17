<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Task;

use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Script\OpenShopsScript;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Override;

final readonly class OpenShopsTask implements Task
{
    /** @param non-empty-list<ShopId> $shops */
    public function __construct(
        public MapName $map,
        public array $shops,
    ) {}

    #[Override]
    public function title(): string
    {
        return 'Open ' . count($this->shops) . " shops in {$this->map->value}";
    }

    #[Override]
    public function script(Character $me): Script
    {
        return new OpenShopsScript($me->name(), $me->position(), $this->map, $this->shops);
    }
}
