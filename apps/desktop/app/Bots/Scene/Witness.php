<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

use Lorekeeper\Client\Character;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Combat\MonstersHealthChangedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Reward\MonsterRewardedEvent;

final class Witness
{
    private ?Character $me = null;

    private Room $room;

    private Pulse $pulse;

    private Feed $feed;

    private int $captured = 0;

    private ?int $rank = null;

    private ?string $seen = null;

    private ?bool $fallen = null;

    public function __construct()
    {
        $this->room = new Room();
        $this->pulse = new Pulse();
        $this->feed = new Feed();
    }

    public function monsterName(MonsterSpawnId $id): ?string
    {
        return $this->room
            ->monster($id)
            ?->monster
            ->name
            ->value;
    }

    public function saw(Character $me, int $now): ?Scene
    {
        $first = $this->me === null;
        $this->me = $me;
        if ($first) {
            $this->room = Room::of($me->location()->map);
        }

        $class = $me->player()->class;
        $rank = $class->rank()->value;
        if ($this->rank !== null && $rank > $this->rank) {
            $this->feed = $this->feed->add(
                $now,
                FeedKind::RankedUp,
                "{$class->class->name->value} reached rank {$rank}",
            );
        }

        $fallen = $me->state() === CombatState::Dead;
        if ($this->fallen !== null && $this->fallen !== $fallen) {
            $this->feed = $fallen
                ? $this->feed->add($now, FeedKind::Died, 'Fell in battle')
                : $this->feed->add($now, FeedKind::Revived, 'Back on their feet');
        }

        $this->fallen = $fallen;

        $seen = implode('|', [
            $me->state()->name,
            $me->location()->map->name->value,
            $me->player()->progress->level->value,
            $rank,
            $me->away() ? 'away' : 'here',
        ]);
        $changed = $first || $seen !== $this->seen;
        $this->rank = $rank;
        $this->seen = $seen;

        return $changed ? $this->capture($now) : $this->throttled($now);
    }

    public function heard(Event $event, int $now): ?Scene
    {
        $me = $this->me;
        if ($me === null) {
            return null;
        }

        if ($event instanceof MonsterRewardedEvent) {
            $this->rewarded($event, $now);
        }

        $this->room = $this->room->heard($event, $me->player()->name, $now);
        $this->feed = $this->remembered($event, $now);

        return $event instanceof MonstersHealthChangedEvent ? $this->throttled($now) : $this->capture($now);
    }

    private function remembered(Event $event, int $now): Feed
    {
        return match (true) {
            $event instanceof LeveledUpEvent => $this->feed->add(
                $now,
                FeedKind::LeveledUp,
                "Reached level {$event->level->value}",
            ),
            $event instanceof MovedToMapEvent => $this->feed->add(
                $now,
                FeedKind::Travelled,
                "Arrived in {$event->map->name->value}",
            ),
            default => $this->feed,
        };
    }

    private function rewarded(MonsterRewardedEvent $event, int $now): void
    {
        $name = $this->room
            ->monster($event->monster)
            ?->monster
            ->name
            ->value ?? 'a monster';
        $this->pulse = $this->pulse->rewarded($now, $event->experience->value);
        $this->feed = $this->feed->add(
            $now,
            FeedKind::Defeated,
            "Defeated {$name}",
            '+' . number_format($event->experience->value) . ' XP · +' . number_format($event->gold->value) . ' gold',
        );
    }

    private function throttled(int $now): ?Scene
    {
        return ($now - $this->captured) < 1 ? null : $this->capture($now);
    }

    private function capture(int $now): ?Scene
    {
        $me = $this->me;
        if ($me === null) {
            return null;
        }

        $this->captured = $now;

        return Scene::capture($me, $this->room, $this->pulse, $this->feed, $now);
    }
}
