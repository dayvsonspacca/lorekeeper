<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Closure;
use Generator;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapRoom;
use Override;

/**
 * @template TResult
 *
 * @implements Script<TResult>
 */
final readonly class PrivateRooms implements Script
{
    public const int LOWEST = 10_000;

    public const int HIGHEST = 99_999;

    /** @var Closure(MapName): MapRoom */
    private Closure $rooms;

    /**
     * @param Script<TResult> $script
     * @param null|Closure(MapName): MapRoom $rooms
     */
    public function __construct(
        private Script $script,
        ?Closure $rooms = null,
    ) {
        $this->rooms =
            $rooms ?? static fn(MapName $map): MapRoom => new MapRoom(random_int(self::LOWEST, self::HIGHEST));
    }

    /**
     * @template TPlayed
     *
     * @param Script<TPlayed> $script
     *
     * @return Script<TPlayed>
     */
    public static function asPreferred(Script $script, Preferences $preferences): Script
    {
        if (!$preferences->private_rooms) {
            return $script;
        }

        return new self($script, $preferences->play_together ? SharedRooms::of(...) : null);
    }

    #[Override]
    public function title(): string
    {
        return $this->script->title();
    }

    #[Override]
    public function run(Character $me): Generator
    {
        $steps = $this->script->run($me);
        while ($steps->valid()) {
            $instruction = $steps->current();
            if ($instruction instanceof Send && $instruction->command instanceof JoinMapCommand) {
                $instruction = new Send(
                    $instruction->command->inRoom(($this->rooms)($instruction->command->mapName)),
                    $instruction->await,
                );
            }

            $steps->send(yield $instruction);
        }

        return $steps->getReturn();
    }
}
