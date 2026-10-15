<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Miner\Cli\Arguments;
use Lorekeeper\Miner\Cli\Password;
use Lorekeeper\Miner\Cli\ServerChoice;
use Lorekeeper\Miner\Connection;
use Lorekeeper\Miner\Discovery\FfdecMapDiscoverer;
use Lorekeeper\Miner\Discovery\MapQueue;
use Lorekeeper\Miner\Discovery\QuestQueue;
use Lorekeeper\Miner\Listener;
use Lorekeeper\Miner\Login\GameLogin;
use Lorekeeper\Miner\Login\LoginClient;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Miner\Routine\DiscoverMapsRoutine;
use Lorekeeper\Miner\Routine\DiscoverQuestsRoutine;
use Lorekeeper\Miner\Routine\GameEntry;
use Lorekeeper\Miner\Routine\HouseRoutine;
use Lorekeeper\Miner\Routine\ListenRoutine;
use Lorekeeper\Miner\Routine\TravelMapRoutine;
use Lorekeeper\Miner\Session;
use Lorekeeper\Miner\TravelMap\TravelMapClient;
use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Store\FactStore;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use Lorekeeper\Protocol\ClientVersion;
use Lorekeeper\Protocol\Message\Message;
use Psl\Async;
use Psl\Async\Scheduler;
use Psl\Async\SignalCancellationToken;
use Psl\DateTime\Duration;

const USAGE = <<<'TXT'
    Usage: just mine <routine> [options]

    Routines:
      listen       --user=Name [--duration=60] [--server=Twilly] [--version=5.1]
      house        --user=Name [--server=Twilly] [--version=5.1]   (until Ctrl+C)
      discover-maps --user=Name [--interval=5] [--limit=50] [--server=Twilly]
      discover-quests --user=Name [--interval=2] [--limit=20] [--server=Twilly]
      travel-map

    Common options:
      --db=lorekeeper.sqlite

    The password is read from AQW_PASSWORD or asked interactively.

    TXT;

function log_line(string $line): void
{
    fwrite(STDOUT, data: date('H:i:s') . " {$line}\n");
}

function now(): DateTimeImmutable
{
    return new DateTimeImmutable();
}

function usage(): int
{
    fwrite(STDERR, data: USAGE);

    return 2;
}

function travel_map(FactStore $store): int
{
    new TravelMapRoutine(new TravelMapClient()->fetch(...), new Recorder($store, now(...)), log_line(...))->run();

    return 0;
}

function record_unrecognized(Message $message): void
{
    if (!is_dir('var')) {
        mkdir('var');
    }

    $line = json_encode(['at' => now()->format(DATE_ATOM), 'raw' => $message->raw], JSON_THROW_ON_ERROR);
    file_put_contents('var/unrecognized.jsonl', $line . "\n", FILE_APPEND);
}

/** @param Closure(GameEntry): void $routine */
function in_game(Arguments $arguments, FactStore $store, Closure $routine): int
{
    $player = new PlayerName($arguments->get('user'));
    $login = new LoginClient()->login($player, Password::read());
    $server = ServerChoice::pick($login->servers, $arguments->has('server') ? $arguments->get('server') : null);

    if ($server->isNone()) {
        fwrite(STDERR, data: "No matching online server.\n");

        return 1;
    }

    log_line("server {$server->unwrap()->name}");

    $connection = Connection::open($server->unwrap());
    $session = new Session();
    $listener = new Listener(
        $connection,
        new Recorder($store, now(...)),
        $session,
        log_line(...),
        record_unrecognized(...),
    );
    $reader = Async\run($listener->listen(...));

    try {
        $routine(
            new GameEntry(
                $connection,
                new GameLogin($player, $login->token, new ClientVersion($arguments->get('version', '5.1'))),
                $session,
                log_line(...),
            ),
        );
    } finally {
        Async\sleep(Duration::seconds(1));
        $connection->close();
        $reader->await();
    }

    $stats = $listener->stats();
    log_line("done: {$stats['messages']} messages, {$stats['events']} events, {$stats['facts']} facts recorded");

    return 0;
}

function listen(Arguments $arguments, FactStore $store): int
{
    $duration = Duration::seconds((int) $arguments->get('duration', '60'));

    return in_game($arguments, $store, static function (GameEntry $entry) use ($duration): void {
        new ListenRoutine($entry, log_line(...))->run($duration);
    });
}

/** @param Closure(SignalCancellationToken): int $body */
function until_interrupted(Closure $body): int
{
    $stop = new SignalCancellationToken();
    $signal = Scheduler::onSignal(SIGINT, static function () use ($stop): void {
        $stop->cancel();
    });

    try {
        return $body($stop);
    } finally {
        Scheduler::cancel($signal);
    }
}

function discover_maps(Arguments $arguments, FactStore $store): int
{
    $interval = Duration::seconds(max(1, (int) $arguments->get('interval', '5')));
    $limit = max(1, (int) $arguments->get('limit', '50'));
    $queue = new MapQueue($store, now(...), Duration::hours(7 * 24));
    $discoverer = new FfdecMapDiscoverer(__DIR__ . '/decompile.sh', 'var/swfs');

    return until_interrupted(static fn(SignalCancellationToken $stop): int => in_game(
        $arguments,
        $store,
        static function (GameEntry $entry) use ($queue, $discoverer, $store, $interval, $limit, $stop): void {
            new DiscoverMapsRoutine($entry, $queue, $discoverer, new Recorder($store, now(...)), log_line(...))->run(
                $interval,
                $limit,
                $stop,
            );
        },
    ));
}

function discover_quests(Arguments $arguments, FactStore $store): int
{
    $interval = Duration::seconds(max(1, (int) $arguments->get('interval', '2')));
    $limit = max(1, (int) $arguments->get('limit', '20'));
    $queue = new QuestQueue($store);

    return until_interrupted(static fn(SignalCancellationToken $stop): int => in_game(
        $arguments,
        $store,
        static function (GameEntry $entry) use ($queue, $interval, $limit, $stop): void {
            new DiscoverQuestsRoutine($entry, $queue, log_line(...))->run($interval, $limit, $stop);
        },
    ));
}

function house(Arguments $arguments, FactStore $store): int
{
    return until_interrupted(static fn(SignalCancellationToken $stop): int => in_game(
        $arguments,
        $store,
        static function (GameEntry $entry) use ($stop): void {
            new HouseRoutine($entry, log_line(...))->run($stop);
        },
    ));
}

Async\main(static function () use ($argv): int {
    $arguments = Arguments::parse($argv);
    $store = SqliteFactStore::open($arguments->get('db', 'lorekeeper.sqlite'), DefaultCodecs::create());

    return match (true) {
        $arguments->routine === 'travel-map' => travel_map($store),
        $arguments->routine === 'listen' && $arguments->has('user') => listen($arguments, $store),
        $arguments->routine === 'house' && $arguments->has('user') => house($arguments, $store),
        $arguments->routine === 'discover-maps' && $arguments->has('user') => discover_maps($arguments, $store),
        $arguments->routine === 'discover-quests' && $arguments->has('user') => discover_quests($arguments, $store),
        default => usage(),
    };
});
