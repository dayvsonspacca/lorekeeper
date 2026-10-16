<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Bots\StepLog;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Client\Client;
use Lorekeeper\Client\ClientVersion;
use Lorekeeper\Client\Connection;
use Lorekeeper\Client\Login\GameLogin;
use Lorekeeper\Client\Login\LoginClient;
use Lorekeeper\Client\Login\ServerChoice;
use Lorekeeper\Client\Script\Disconnected;
use Lorekeeper\Client\Script\Game\LogoutScript;
use Lorekeeper\Client\Script\Instruction;
use Lorekeeper\Client\Script\Stopped;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Event;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(name: 'bots:run', description: 'Play one bot run until it ends or is stopped')]
final class RunBot extends Command
{
    /** @var string */
    protected $signature = 'bots:run {run}';

    public function handle(LoginClient $logins): int
    {
        $run = BotRun::query()
            ->with('account')
            ->findOrFail((int) $this->argument('run'));
        $run->update(['status' => RunStatus::Running, 'pid' => getmypid(), 'started_at' => now()]);

        $stop = new SignalCancellationToken();
        $signals = [];
        foreach ([SIGTERM, SIGINT] as $signal) {
            $signals[] = Scheduler::onSignal($signal, static function () use ($stop): void {
                $stop->cancel();
            });
        }

        try {
            [$status, $result] = $this->play($run, $logins, $stop);
        } catch (Throwable $failure) {
            [$status, $result] = [RunStatus::Failed, $failure->getMessage()];
        } finally {
            foreach ($signals as $signal) {
                Scheduler::cancel($signal);
            }
        }

        $run->log("= {$status->value}" . ($result === null ? '' : ": {$result}"));
        $run->update(['status' => $status, 'result' => $result, 'pid' => null, 'finished_at' => now()]);

        return self::SUCCESS;
    }

    /** @return array{RunStatus, null|string} */
    private function play(BotRun $run, LoginClient $logins, SignalCancellationToken $stop): array
    {
        $player = new PlayerName($run->account->name);

        $run->log('> login');
        $login = $logins->login($player, $run->account->password);

        $server = ServerChoice::pick($login->servers, $run->account->server);
        if ($server->isNone()) {
            return [RunStatus::Failed, 'no open server'];
        }

        $run->log("> connect to {$server->unwrap()->name}");
        $connection = Connection::open($server->unwrap());
        $client = new Client($connection, null, static function (
            Instruction $instruction,
            null|Event|TimedOut $answer,
        ) use ($run): void {
            $run->log(StepLog::describe($instruction, $answer));
        });

        $result = $client->run(
            $run->script->script(new GameLogin($player, $login->token, new ClientVersion('5.1')), $run->parameters),
            $stop,
        );

        if ($result instanceof Disconnected) {
            return [RunStatus::Disconnected, $result->reason];
        }

        $client->run(new LogoutScript());
        $connection->close();

        return $result instanceof Stopped ? [RunStatus::Stopped, null] : [RunStatus::Failed, StepLog::name($result)];
    }
}
