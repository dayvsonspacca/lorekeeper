<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Closure;
use Generator;
use Lorekeeper\Client\Character;
use Lorekeeper\Client\Command\Map\JoinMapCommand;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Server\RoomId;
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

    /** @var Closure(MapName): RoomId */
    private Closure $rooms;

    /**
     * @param Script<TResult> $script
     * @param null|Closure(MapName): RoomId $rooms
     */
    public function __construct(
        private Script $script,
        ?Closure $rooms = null,
    ) {
        $this->rooms = $rooms ?? static fn(MapName $map): RoomId => new RoomId(random_int(self::LOWEST, self::HIGHEST));
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
