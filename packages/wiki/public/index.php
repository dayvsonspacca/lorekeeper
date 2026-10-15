<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use Lorekeeper\Persistence\Codec\DefaultCodecs;
use Lorekeeper\Persistence\Store\SqliteFactStore;
use Lorekeeper\Wiki\Api\Application;
use Lorekeeper\Wiki\SwfProxy;

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$proxied = match (true) {
    str_starts_with($path, '/proxy/swf/monster/') => static fn(SwfProxy $proxy) => $proxy->monster(substr(
        $path,
        strlen('/proxy/swf/monster/'),
    )),
    str_starts_with($path, '/proxy/swf/item/') => static fn(SwfProxy $proxy) => $proxy->item(substr(
        $path,
        strlen('/proxy/swf/item/'),
    )),
    default => null,
};

if ($proxied !== null) {
    $swf = $proxied(new SwfProxy(__DIR__ . '/../../../var/cache/swf'));
    header('Access-Control-Allow-Origin: *');
    if ($swf->isNone()) {
        http_response_code(404);

        return;
    }

    header('Content-Type: application/x-shockwave-flash');
    header('Cache-Control: public, max-age=604800');
    echo $swf->unwrap();

    return;
}

if (!str_starts_with($path, '/api/')) {
    $file = __DIR__ . '/app' . $path;
    if ($path !== '/' && is_file($file)) {
        $types = [
            'js' => 'text/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'woff2' => 'font/woff2',
            'png' => 'image/png',
        ];
        header('Content-Type: ' . ($types[pathinfo($file, PATHINFO_EXTENSION)] ?? 'application/octet-stream'));
        readfile($file);

        return;
    }

    $index = __DIR__ . '/app/index.html';
    header('Content-Type: text/html; charset=utf-8');
    echo is_file($index) ? file_get_contents($index) : 'Run `just wiki-build` first.';

    return;
}

$database = getenv('LOREKEEPER_DB');
$database = is_string($database) && $database !== '' ? $database : __DIR__ . '/../../../lorekeeper.sqlite';
$codecs = DefaultCodecs::create();

$response = new Application(SqliteFactStore::open($database, $codecs), $codecs)->handle($path);

http_response_code($response->status);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
echo $response->body();
