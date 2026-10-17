<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Log;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Client\Command\Map\JoinHouseCommand;
use Lorekeeper\Client\Command\Map\JoinInitialMapCommand;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Map\MapLockedEvent;
use Lorekeeper\Server\Event\Map\MapMemberOnlyEvent;
use Lorekeeper\Server\Event\Map\MapNotAvailableEvent;
use Lorekeeper\Server\Event\Map\MapRequiresQuestEvent;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Event\Progression\LeveledUpEvent;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Lorekeeper\Server\Event\Session\ConnectionEstablishedEvent;
use Lorekeeper\Server\Event\Session\LoginFailedEvent;
use Lorekeeper\Server\Event\Session\LoginSucceededEvent;
use Lorekeeper\Server\Event\Shop\ShopLoadedEvent;

final readonly class StepLog
{
    /** @return array{LogLevel, string} */
    public static function describe(Instruction $instruction, null|Event|TimedOut $answer): array
    {
        return match (true) {
            $instruction instanceof Send && $answer instanceof TimedOut => [
                LogLevel::Warning,
                'No answer to '
                    . self::action($instruction->command)
                    . " after {$answer->await->timeout->getTotalSeconds()}s",
            ],
            $instruction instanceof Send && $answer instanceof Event => self::answered($instruction->command, $answer),
            $instruction instanceof Send && $instruction->command instanceof LogoutCommand => [
                LogLevel::Info,
                'Logged out',
            ],
            $instruction instanceof Send => [LogLevel::Info, 'Sent ' . self::action($instruction->command)],
            $instruction instanceof Await && $answer instanceof TimedOut => [
                LogLevel::Warning,
                "Nothing arrived after {$answer->await->timeout->getTotalSeconds()}s",
            ],
            $instruction instanceof Await && $answer instanceof Event => self::answered(null, $answer),
            $instruction instanceof Pause => [
                LogLevel::Info,
                "Idling for {$instruction->duration->getTotalSeconds()}s",
            ],
            $instruction instanceof Progress => [LogLevel::Info, "Progress {$instruction->done}/{$instruction->total}"],
            default => [LogLevel::Warning, 'Unknown step'],
        };
    }

    public static function action(Command $command): string
    {
        $class = $command::class;
        $name = (string) preg_replace(
            '/Command$/',
            replacement: '',
            subject: substr($class, (int) strrpos($class, needle: '\\') + 1),
        );

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', replacement: ' $0', subject: $name));
    }

    /** @return array{LogLevel, string} */
    private static function answered(?Command $command, Event $event): array
    {
        return match (true) {
            $event instanceof ConnectionEstablishedEvent => [LogLevel::Info, 'Connected'],
            $event instanceof LoginSucceededEvent => [LogLevel::Success, 'Logged in'],
            $event instanceof LoginFailedEvent => [LogLevel::Error, 'Login refused'],
            $event instanceof LeveledUpEvent => [LogLevel::Success, $event->summary()],
            $event instanceof PlayerDiedEvent => [LogLevel::Warning, $event->summary()],
            $event instanceof MovedToMapEvent && $command instanceof JoinInitialMapCommand => [
                LogLevel::Success,
                "Entered the game in {$event->map->value} (room {$event->roomId->value})",
            ],
            $event instanceof MovedToMapEvent && $command instanceof JoinHouseCommand => [
                LogLevel::Success,
                "Entered the house in {$event->map->value}",
            ],
            $event instanceof MovedToMapEvent => [LogLevel::Success, 'Moved to ' . $event->summary()],
            $event instanceof PlayerInventoryLoadedEvent => [
                LogLevel::Info,
                'Inventory loaded: ' . count($event->items) . ' items',
            ],
            $event instanceof MapLockedEvent,
            $event instanceof MapMemberOnlyEvent,
            $event instanceof MapNotAvailableEvent,
            $event instanceof MapRequiresQuestEvent,
                => [LogLevel::Warning, $event->summary()],
            $event instanceof ShopLoadedEvent => [LogLevel::Info, 'Opened ' . $event->summary()],
            $event instanceof QuestLoadedEvent => [LogLevel::Info, 'Loaded ' . $event->summary()],
            default => [LogLevel::Info, $event->summary()],
        };
    }
}
