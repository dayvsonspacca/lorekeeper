<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Console\Commands;

use Illuminate\Console\Command;
use Lorekeeper\Apps\Wiki\Exploring\Expedition;
use Lorekeeper\Apps\Wiki\Exploring\Scribe;
use Lorekeeper\Apps\Wiki\Exploring\ScriptFile;
use Lorekeeper\Client\Session\Credentials;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\LinkedCancellationToken;
use Psl\Async\SignalCancellationToken;
use Psl\Async\TimeoutCancellationToken;
use Psl\DateTime\Duration;
use Revolt\EventLoop;
use RuntimeException;
use Throwable;

final class Explore extends Command
{
    /** @var string */
    protected $signature = 'wiki:explore {script : A PHP file that returns the script to play} {--for=60 : Seconds to play before stopping}';

    /** @var string */
    protected $description = 'Play a script in the game with AQW_PLAYER and AQW_PASSWORD and keep what the game tells';

    public function handle(Expedition $expedition): int
    {
        $player = (string) getenv('AQW_PLAYER');
        $password = (string) getenv('AQW_PASSWORD');
        $server = (string) getenv('AQW_SERVER');
        if ($player === '' || $password === '') {
            $this->error('Set AQW_PLAYER and AQW_PASSWORD to play.');

            return self::FAILURE;
        }

        try {
            $script = ScriptFile::load((string) $this->argument('script'));
        } catch (RuntimeException $missing) {
            $this->error($missing->getMessage());

            return self::FAILURE;
        }

        $stop = new SignalCancellationToken();
        if (extension_loaded('pcntl')) {
            /** @mago-expect lint:prefer-first-class-callable */
            EventLoop::unreference(EventLoop::onSignal(SIGINT, static fn() => $stop->cancel()));
        }

        $scribe = new Scribe();
        try {
            $summary = $expedition->explore(
                new Credentials(new PlayerName($player), $password, $server === '' ? null : $server),
                $script,
                $scribe,
                new LinkedCancellationToken(
                    $stop,
                    new TimeoutCancellationToken(Duration::seconds(max(1, (int) $this->option('for')))),
                ),
            );
        } catch (Throwable $failure) {
            $this->error("Failed: {$failure->getMessage()}");

            return self::FAILURE;
        }

        $this->info($summary);
        $this->line("{$scribe->facts()} facts kept");

        return self::SUCCESS;
    }
}
