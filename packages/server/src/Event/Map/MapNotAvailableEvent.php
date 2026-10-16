<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Notice;
use Lorekeeper\Server\Message\Message;
use Override;
use Psl\Option;

final readonly class MapNotAvailableEvent implements Event
{
    public function __construct(
        public MapName $mapName,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(Message $message): Option\Option
    {
        return Notice::text($message, 'warning')->andThen(static function (string $text): Option\Option {
            $match = [];

            if (preg_match('/^(\S+) is not available/i', $text, $match) !== 1) {
                return Option\none();
            }

            return Option\some($match[1]);
        })->map(static fn(string $name): static => new static(new MapName($name)));
    }
}
