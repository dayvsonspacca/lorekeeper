<?php

declare(strict_types=1);

namespace Lorekeeper\Miner;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Protocol\AreaId;
use Lorekeeper\Protocol\Event\Event;
use Lorekeeper\Protocol\Event\LoginSucceededEvent;
use Lorekeeper\Protocol\Event\Map\MovedToMapEvent;
use Lorekeeper\Protocol\SocketId;
use Psl\Option;

final class Session
{
    private ?SocketId $socketId = null;

    private ?AreaId $areaId = null;

    private ?MapName $mapName = null;

    public function observe(Event $event): void
    {
        if ($event instanceof LoginSucceededEvent) {
            $this->socketId = $event->socketId;
        }

        if ($event instanceof MovedToMapEvent) {
            $this->areaId = $event->areaId;
            $this->mapName = $event->mapName;
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

    /** @return Option\Option<MapName> */
    public function mapName(): Option\Option
    {
        return Option\from_nullable($this->mapName);
    }
}
