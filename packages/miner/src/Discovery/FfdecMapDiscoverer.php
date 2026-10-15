<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Miner\Exception\DiscoveryFailedException;
use Override;

final readonly class FfdecMapDiscoverer implements MapDiscoverer
{
    private const string MAPS_URL = 'https://game.aq.com/game/gamefiles/maps/';

    public function __construct(
        private string $decompileScript,
        private string $workDirectory,
    ) {}

    /** @throws DiscoveryFailedException */
    #[Override]
    public function discover(Gamefile $mapFile): MapAnalysis
    {
        $workspace = $this->workDirectory . '/' . bin2hex(random_bytes(6));
        $swf = "{$workspace}/map.swf";
        $output = "{$workspace}/decompiled";

        mkdir($workspace, recursive: true);

        try {
            $this->download($mapFile, $swf);
            $this->decompile($swf, $output);

            return new MapAnalysis(MapNameExtractor::fromDirectory($output), NpcExtractor::fromDirectory($output));
        } finally {
            self::remove($workspace);
        }
    }

    private function download(Gamefile $mapFile, string $target): void
    {
        $curl = curl_init(self::MAPS_URL . $mapFile->value);
        $handle = fopen($target, mode: 'wb');

        curl_setopt_array($curl, [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $ok = curl_exec($curl);
        fclose($handle);

        if ($ok !== true) {
            throw new DiscoveryFailedException("Could not download {$mapFile}: " . curl_error($curl));
        }
    }

    private function decompile(string $swf, string $output): void
    {
        $exitCode = 0;
        $lines = [];
        exec(
            escapeshellarg($this->decompileScript)
            . ' '
            . escapeshellarg($swf)
            . ' '
            . escapeshellarg($output)
            . ' 2>&1',
            $lines,
            $exitCode,
        );

        if ($exitCode !== 0) {
            throw new DiscoveryFailedException('Could not decompile: ' . implode("\n", $lines));
        }
    }

    private static function remove(string $path): void
    {
        if (is_file($path)) {
            unlink($path);

            return;
        }

        $entries = scandir($path);

        foreach ($entries === false ? [] : $entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            self::remove("{$path}/{$entry}");
        }

        rmdir($path);
    }
}
