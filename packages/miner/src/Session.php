<?php

declare(strict_types=1);

namespace Lorekeeper\Miner;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Event\Event;
use Lorekeeper\Protocol\Event\LoginSucceededEvent;
use Lorekeeper\Protocol\Event\Map\MovedToMapEvent;
use Lorekeeper\Protocol\Event\Shop\ShopLoadedEvent;
use Lorekeeper\Protocol\SocketId;
use Psl\Option;

final class Session
{
    private ?SocketId $socketId = null;

    private ?AreaId $areaId = null;

    private ?MapName $mapName = null;

    private ?Gamefile $mapFile = null;

    /** @var array<int, true> */
    private array $loadedShops = [];

    public function observe(Event $event): void
    {
        if ($event instanceof LoginSucceededEvent) {
            $this->socketId = $event->socketId;
        }

        if ($event instanceof ShopLoadedEvent) {
            $this->loadedShops[$event->shopId->value] = true;
        }

        if ($event instanceof MovedToMapEvent) {
            $this->areaId = $event->areaId;
            $this->mapName = $event->mapName;
            $this->mapFile = null;
            foreach ($event->facts as $fact) {
                if (!$fact->value instanceof Gamefile || !$fact->subject instanceof MapName) {
                    continue;
                }

                $this->mapFile = $fact->value;
            }
        }
    }

    /** @return Option\Option<SocketId> */
    public function socketId(): Option\Option
    {
        return Option\from_nullable($this->socketId);
    }

    /** @return Option\Option<AreaId> */
    public function areaId(): Option\Option
    {
        return Option\from_nullable($this->areaId);
    }

    /** @return Option\Option<Gamefile> */
    public function mapFile(): Option\Option
    {
        return Option\from_nullable($this->mapFile);
    }

    public function hasLoaded(ShopId $shop): bool
    {
        return array_key_exists($shop->value, $this->loadedShops);
    }

    /** @return Option\Option<MapName> */
    public function mapName(): Option\Option
    {
        return Option\from_nullable($this->mapName);
    }
}
