<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Wiki\Bots\LogLevel;
use Lorekeeper\Apps\Wiki\Bots\RunStatus;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Psl\Async;
use Psl\Async\Exception\CancelledException;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

/**
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
#[AsCommand(name: 'bots:supervise', description: 'Start a process for each pending bot run and stop the ones asked to')]
final class SuperviseBots extends Command
{
    /** @var string */
    protected $signature = 'bots:supervise';

    /** @var array<int, Process> */
    private array $processes = [];

    /** @var array<int, true> */
    private array $signalled = [];

    public function handle(): int
    {
        $this->recover();

        $stop = new SignalCancellationToken();
        $signals = [];
        foreach ([SIGTERM, SIGINT] as $signal) {
            $signals[] = Scheduler::onSignal($signal, static function () use ($stop): void {
                $stop->cancel();
            });
        }

        while (!$stop->isCancelled()) {
            $this->tick();

            try {
                Async\sleep(Duration::seconds(1), $stop);
            } catch (CancelledException) {
                break;
            }
        }

        foreach ($signals as $signal) {
            Scheduler::cancel($signal);
        }

        $this->shutdown();

        return self::SUCCESS;
    }

    public function tick(): void
    {
        foreach (BotRun::query()->where('status', RunStatus::Pending)->get() as $run) {
            $this->start($run);
        }

        foreach (BotRun::query()->where('status', RunStatus::Stopping)->get() as $run) {
            $this->stop($run);
        }

        $this->reap();
    }

    private function recover(): void
    {
        $orphans = BotRun::query()->whereIn('status', [RunStatus::Running, RunStatus::Stopping])->get();

        foreach ($orphans as $run) {
            if ($run->pid !== null && posix_kill($run->pid, signal: 0)) {
                posix_kill($run->pid, SIGTERM);

                continue;
            }

            $run->log('Failed: interrupted', LogLevel::Error);
            $run->closeTasks('interrupted');
            $run->update([
                'status' => RunStatus::Failed,
                'result' => 'interrupted',
                'pid' => null,
                'finished_at' => now(),
            ]);
        }
    }

    private function start(BotRun $run): void
    {
        $process = new Process([PHP_BINARY, base_path('artisan'), 'bots:run', (string) $run->id], base_path());
        $process->setTimeout(null);
        $process->start();

        $this->processes[$run->id] = $process;
        $run->update(['status' => RunStatus::Running, 'pid' => $process->getPid()]);
    }

    private function stop(BotRun $run): void
    {
        $process = $this->processes[$run->id] ?? null;

        if ($process === null || !$process->isRunning()) {
            $run->update(['status' => RunStatus::Stopped, 'pid' => null, 'finished_at' => now()]);

            return;
        }

        if (!array_key_exists($run->id, $this->signalled)) {
            $process->signal(SIGTERM);
            $this->signalled[$run->id] = true;
        }
    }

    private function reap(): void
    {
        foreach ($this->processes as $id => $process) {
            if ($process->isRunning()) {
                continue;
            }

            unset($this->processes[$id], $this->signalled[$id]);

            $run = BotRun::query()->find($id);
            if ($run !== null && $run->status->isActive()) {
                $error = trim($process->getErrorOutput() . $process->getOutput());
                $run->log('Failed: the bot process exited' . ($error === '' ? '' : ": {$error}"), LogLevel::Error);
                $run->update([
                    'status' => RunStatus::Failed,
                    'result' => 'process exited',
                    'pid' => null,
                    'finished_at' => now(),
                ]);
            }
        }
    }

    private function shutdown(): void
    {
        foreach ($this->processes as $process) {
            if (!$process->isRunning()) {
                continue;
            }

            $process->signal(SIGTERM);
        }

        foreach ($this->processes as $process) {
            $process->wait();
        }

        $this->reap();
    }
}
