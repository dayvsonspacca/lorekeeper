<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder;

use Lorekeeper\Client\Session\Credentials;
use Lorekeeper\Core\Player\PlayerName;
use Psl\Async\LinkedCancellationToken;
use Psl\Async\SignalCancellationToken;
use Psl\Async\TimeoutCancellationToken;
use Psl\DateTime\Duration;
use Psl\DateTime\Timestamp;
use Psl\IO;
use Revolt\EventLoop;
use SensitiveParameter;
use Throwable;

final readonly class Command
{
    private const string USAGE = <<<'TEXT'
        Usage: AQW_PLAYER=name AQW_PASSWORD=secret bin/record <scenario> [--for=seconds] [--level=n]

        Enters the game, plays the scenario and records every message sent and
        received in records/. AQW_SERVER picks a server; Ctrl+C stops early.

        Scenarios:

        TEXT;

    /**
     * @param list<string> $arguments
     * @param array<string, string> $environment
     */
    public static function run(array $arguments, array $environment, string $records): int
    {
        $screen = IO\output_handle();
        $request = Request::parse($arguments, $environment);
        if ($request === null) {
            $screen->writeAll(self::usage());

            return 1;
        }

        if (!is_dir($records) && !mkdir($records, permissions: 0o755, recursive: true)) {
            $screen->writeAll("Could not create {$records}.\n");

            return 1;
        }

        $path = $records . '/' . Timestamp::now()->format('yyyyMMdd-HHmmss') . "-{$request->scenario->value}.jsonl";
        $stop = new SignalCancellationToken();
        if (extension_loaded('pcntl')) {
            /** @mago-expect lint:prefer-first-class-callable */
            EventLoop::unreference(EventLoop::onSignal(SIGINT, static fn() => $stop->cancel()));
        }

        $recording = null;
        try {
            $summary = new Session()->record(
                new Credentials(new PlayerName($request->player), $request->password, $request->server),
                $request->scenario->script($request->options, $request->length),
                static function (#[SensitiveParameter] string $token) use ($path, $screen, &$recording): Recording {
                    return $recording = new Recording($path, $token, $screen);
                },
                new LinkedCancellationToken(
                    $stop,
                    new TimeoutCancellationToken(
                        $request->scenario === Scenario::Watch
                            ? $request->length->plus(Duration::seconds(30))
                            : $request->length,
                    ),
                ),
            );
        } catch (Throwable $failure) {
            $summary = "Failed: {$failure->getMessage()}";
        }

        $screen->writeAll("\n{$summary}\n" . ($recording?->messages() ?? 0) . " messages recorded in {$path}\n");

        return 0;
    }

    private static function usage(): string
    {
        $usage = self::USAGE;
        foreach (Scenario::cases() as $scenario) {
            $usage .= sprintf("  %-10s %s\n", $scenario->value, $scenario->description());
        }

        return $usage;
    }
}
