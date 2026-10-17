<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

use Lorekeeper\Client\Bot\Credentials;
use Lorekeeper\Client\Bot\Done;
use Lorekeeper\Client\Bot\Log\EventSummary;
use Lorekeeper\Client\Bot\Log\LogLevel;
use Lorekeeper\Client\Bot\Log\StepLog;
use Lorekeeper\Client\Bot\Position;
use Lorekeeper\Client\Bot\Script\WaitScript;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Client\Login\ServerChoice;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Game\EnterGameScript;
use Lorekeeper\Client\Script\Game\InGame;
use Lorekeeper\Client\Script\Game\LogoutScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Progress;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Events;
use Lorekeeper\Server\Event\Map\MovedToMapEvent;
use Lorekeeper\Server\Message\Message;
use Psl\Async\CancellationTokenInterface;
use Psl\Async\LinkedCancellationToken;
use Psl\Async\NullCancellationToken;
use Psl\DateTime\Duration;
use SensitiveParameter;
use Throwable;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final class Session
{
    private ?Position $position = null;

    private ?Job $job = null;

    private bool $idling = false;

    public function __construct(
        private readonly Credentials $credentials,
        private readonly Observer $observer,
        private readonly LoginClient $logins = new LoginClient(),
        private readonly ClientVersion $version = new ClientVersion('5.1'),
    ) {}

    public function play(Jobs $jobs, CancellationTokenInterface $stop = new NullCancellationToken()): Ending
    {
        try {
            $this->say("Logging in as {$this->credentials->player->value}");
            $login = $this->logins->login($this->credentials->player, $this->credentials->password);

            $server = ServerChoice::pick($login->servers, $this->credentials->server);
            if ($server->isNone()) {
                return new Ending(SessionStatus::Failed, 'no open server');
            }

            $this->say("Connecting to {$server->unwrap()->name}");

            return $this->playOn(Connection::open($server->unwrap()), $login->token, $jobs, $stop);
        } catch (Throwable $failure) {
            return new Ending(SessionStatus::Failed, $failure->getMessage());
        }
    }

    public function playOn(
        Connection $connection,
        #[SensitiveParameter]
        string $token,
        Jobs $jobs,
        CancellationTokenInterface $stop = new NullCancellationToken(),
    ): Ending {
        $player = $this->credentials->player;
        $client = new Client($connection, $this->heard(...), $this->stepped(...));

        $entered = $client->run(new EnterGameScript(new GameLogin($player, $token, $this->version)), $stop);
        if ($entered instanceof Disconnected) {
            return new Ending(SessionStatus::Disconnected, $entered->reason);
        }

        if (!$entered instanceof InGame) {
            $connection->close();

            return $entered instanceof Stopped
                ? new Ending(SessionStatus::Stopped)
                : new Ending(SessionStatus::Failed, self::reason($entered));
        }

        if ($this->position === null) {
            $this->at(new Position($entered->map->map, $entered->map->areaId));
        }

        $waiting = false;
        while (!$stop->isCancelled()) {
            $job = $jobs->next();

            if ($job === null) {
                if (!$waiting) {
                    $waiting = true;
                    $this->say('Waiting for tasks in ' . ($this->position?->map->value ?? 'the game'));
                }

                $waited = $client->run(new WaitScript(Duration::seconds(2)), $stop);
                if ($waited instanceof Disconnected) {
                    return new Ending(SessionStatus::Disconnected, $waited->reason);
                }

                continue;
            }

            $waiting = false;
            $dropped = $this->work($client, $jobs, $job, $stop);
            if ($dropped !== null) {
                return new Ending(SessionStatus::Disconnected, $dropped->reason);
            }
        }

        $client->run(new LogoutScript());
        $connection->close();

        return new Ending(SessionStatus::Stopped);
    }

    public function position(): ?Position
    {
        return $this->position;
    }

    private function work(Client $client, Jobs $jobs, Job $job, CancellationTokenInterface $stop): ?Disconnected
    {
        $this->idling = false;
        $this->job = $job;
        $this->observer->started($job);
        $this->say("Task #{$job->id}: {$job->task->title()}");

        $cancel = Watch::until(static fn(): bool => $jobs->cancelled($job));

        try {
            $result = $this->position === null
                ? 'the bot does not know where it is'
                : $client->run(
                    $job->task->script($this->credentials->player, $this->position),
                    new LinkedCancellationToken($stop, $cancel->token()),
                );
        } catch (Throwable $failure) {
            $result = $failure->getMessage();
        } finally {
            $cancel->release();
        }

        [$outcome, $level] = match (true) {
            $result instanceof Done => [new Outcome(JobStatus::Done, $result->summary), LogLevel::Success],
            $result instanceof Disconnected => [new Outcome(JobStatus::Failed, 'disconnected'), LogLevel::Error],
            $result instanceof Stopped && $stop->isCancelled() => [
                new Outcome(JobStatus::Cancelled, 'the run stopped'),
                LogLevel::Warning,
            ],
            $result instanceof Stopped => [new Outcome(JobStatus::Cancelled, 'cancelled'), LogLevel::Warning],
            default => [new Outcome(JobStatus::Failed, self::reason($result)), LogLevel::Error],
        };

        $this->job = null;
        $this->idling = false;
        $this->say("Task #{$job->id} {$outcome->status->value}: {$outcome->summary}", $level);
        $this->observer->finished($job, $outcome);

        return $result instanceof Disconnected ? $result : null;
    }

    private function heard(Message $message): void
    {
        $events = Events::recognize($message);

        foreach ($events as $event) {
            if (!$event instanceof MovedToMapEvent) {
                continue;
            }

            $this->at(new Position($event->map, $event->areaId));
        }

        $this->observer->heard($message, $events);
    }

    private function stepped(Instruction $instruction, null|Event|TimedOut $answer): void
    {
        $this->observer->stepped($instruction, $answer);

        if ($instruction instanceof Progress) {
            if ($this->job !== null) {
                $this->observer->progressed($this->job, $instruction);
            }

            return;
        }

        if ($instruction instanceof Pause) {
            if ($instruction->duration->getTotalSeconds() < 60) {
                return;
            }

            if (!$this->idling) {
                $this->idling = true;
                $this->say($this->position === null ? 'Idling' : "Idling in {$this->position->map->value}");
            }

            return;
        }

        $this->idling = false;
        [$level, $message] = StepLog::describe($instruction, $answer);
        $this->say($message, $level);
    }

    private function at(Position $position): void
    {
        $this->position = $position;
        $this->observer->moved($position);
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
            $result instanceof Event => EventSummary::of($result),
            default => 'the script ended',
        };
    }
}
