<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

use LogicException;
use Lorekeeper\Client\Bot\Character;
use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Log\StepLog;
use Lorekeeper\Client\Bot\Task\Task;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Command\Session\LogoutCommand;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Exception\PeerClosedException;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Client\Login\ServerChoice;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Game\EnterGameScript;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Message\Message;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\NullCancellationToken;
use Psl\DateTime\Duration;
use SensitiveParameter;
use Throwable;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 * @mago-expect lint:too-many-methods
 */
final class Session
{
    private bool $idling = false;

    private bool $connected = true;

    private function __construct(
        private readonly Character $me,
        private readonly Observer $observer,
        private readonly Connection $connection,
        private ?Client $client = null,
    ) {}

    public static function enter(
        Credentials $credentials,
        Observer $observer,
        CancellationTokenInterface $stop = new NullCancellationToken(),
        LoginClient $logins = new LoginClient(),
        ClientVersion $version = new ClientVersion('5.1'),
    ): self|Ending {
        try {
            $observer->said("Logging in as {$credentials->player->value}", LogLevel::Info);
            $login = $logins->login($credentials->player, $credentials->password);

            $server = ServerChoice::pick($login->servers, $credentials->server);
            if ($server->isNone()) {
                return new Ending(SessionStatus::Failed, 'no open server');
            }

            $observer->said("Connecting to {$server->unwrap()->name}", LogLevel::Info);

            return self::enterOn(
                Connection::open($server->unwrap()),
                $credentials->player,
                $login->token,
                $observer,
                $stop,
                $version,
            );
        } catch (Throwable $failure) {
            return new Ending(SessionStatus::Failed, $failure->getMessage());
        }
    }

    /** @mago-expect lint:excessive-parameter-list */
    public static function enterOn(
        Connection $connection,
        PlayerName $player,
        #[SensitiveParameter]
        string $token,
        Observer $observer,
        CancellationTokenInterface $stop = new NullCancellationToken(),
        ClientVersion $version = new ClientVersion('5.1'),
    ): self|Ending {
        $session = new self(new Character($player), $observer, $connection);
        $client = new Client($connection, $session->heard(...), $session->stepped(...));
        $session->client = $client;

        $entered = $client->run(new EnterGameScript(new GameLogin($player, $token, $version)), $stop);
        if ($entered instanceof Disconnected) {
            return new Ending(SessionStatus::Disconnected, $entered->reason);
        }

        if (!$entered instanceof InGame) {
            $connection->close();

            return $entered instanceof Stopped
                ? new Ending(SessionStatus::Stopped)
                : new Ending(SessionStatus::Failed, self::reason($entered));
        }

        $session->me->entered($entered);

        return $session;
    }

    public function character(): Character
    {
        return $this->me;
    }

    public function connected(): bool
    {
        return $this->connected;
    }

    public function play(Task $task, CancellationTokenInterface $cancel = new NullCancellationToken()): Outcome
    {
        $this->idling = false;
        $this->say("Playing {$task->title()}");

        try {
            $result = $this->client()->run($task->script($this->me), $cancel);
        } catch (Throwable $failure) {
            $result = $failure->getMessage();
        }

        [$outcome, $level] = match (true) {
            $result instanceof Done => [new Outcome(OutcomeStatus::Done, $result->summary), LogLevel::Success],
            $result instanceof Disconnected => [
                new Outcome(OutcomeStatus::Disconnected, $result->reason),
                LogLevel::Error,
            ],
            $result instanceof Stopped => [new Outcome(OutcomeStatus::Cancelled, 'cancelled'), LogLevel::Warning],
            default => [new Outcome(OutcomeStatus::Failed, self::reason($result)), LogLevel::Error],
        };

        if ($outcome->status === OutcomeStatus::Disconnected) {
            $this->connected = false;
        }

        $this->idling = false;
        $this->say("{$task->title()}: {$outcome->summary}", $level);

        return $outcome;
    }

    public function wait(Duration $duration, CancellationTokenInterface $stop = new NullCancellationToken()): bool
    {
        $waited = $this->client()->wait($duration, $stop);
        if ($waited instanceof Disconnected) {
            $this->connected = false;
        }

        return $this->connected;
    }

    public function leave(): void
    {
        if ($this->connected) {
            try {
                $this->client()->send(new LogoutCommand());
            } catch (PeerClosedException $closed) {
                $this->say("Could not log out: {$closed->getMessage()}", LogLevel::Warning);
            }
        }

        $this->connection->close();
        $this->connected = false;
    }

    private function client(): Client
    {
        return $this->client ?? throw new LogicException('The session has no client.');
    }

    private function heard(Message $message): void
    {
        $events = Events::recognize($message);

        foreach ($events as $event) {
            if (!$event instanceof Event) {
                continue;
            }

            $this->me->hear($event);
            if ($event instanceof MovedToMapEvent) {
                $this->observer->moved($this->me->position());
            }
        }

        $this->observer->heard($message, $events);
    }

    private function stepped(Instruction $instruction, null|Event|TimedOut $answer): void
    {
        $this->observer->stepped($instruction, $answer);

        if ($instruction instanceof Progress) {
            $this->observer->progressed($instruction);

            return;
        }

        if ($instruction instanceof Pause) {
            if ($instruction->duration->getTotalSeconds() < 60) {
                return;
            }

            if (!$this->idling) {
                $this->idling = true;
                $this->say("Idling in {$this->me->position()->map->value}");
            }

            return;
        }

        if ($instruction instanceof Await && $answer instanceof TimedOut) {
            return;
        }

        $this->idling = false;
        [$level, $message] = StepLog::describe($instruction, $answer);
        $this->say($message, $level);
    }

    private function say(string $message, LogLevel $level = LogLevel::Info): void
    {
        $this->observer->said($message, $level);
    }

    private static function reason(mixed $result): string
    {
        return match (true) {
            is_string($result) => $result,
            $result instanceof TimedOut => 'no answer while waiting for '
                . implode(', ', array_map(static fn(string $event): string => substr(
                    $event,
                    (int) strrpos($event, needle: '\\') + 1,
                ), $result->await->events)),
            $result instanceof Event => $result->summary(),
            default => 'the script ended',
        };
    }
}
