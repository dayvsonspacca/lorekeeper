<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki;

use Psl\Option;

final readonly class SwfProxy
{
    private const string MONSTERS = 'https://game.aq.com/game/gamefiles/mon/';

    private const string GAMEFILES = 'https://game.aq.com/game/gamefiles/';

    private const string VALID = '/^[A-Za-z0-9_\-]+(\/[A-Za-z0-9_\-]+)*\.swf$/';

    public function __construct(
        private string $cacheDirectory,
    ) {}

    /** @return Option\Option<string> */
    public function monster(string $file): Option\Option
    {
        return $this->fetch(self::MONSTERS, $file);
    }

    /** @return Option\Option<string> */
    public function item(string $file): Option\Option
    {
        return str_starts_with($file, 'items/') || str_starts_with($file, 'classes/')
            ? $this->fetch(self::GAMEFILES, $file)
            : Option\none();
    }

    /** @return Option\Option<string> */
    private function fetch(string $base, string $file): Option\Option
    {
        if (preg_match(self::VALID, $file) !== 1) {
            return Option\none();
        }

        $cached = $this->cacheDirectory . '/' . sha1($base . $file) . '.swf';
        if (is_file($cached)) {
            return Option\some((string) file_get_contents($cached));
        }

        $curl = curl_init($base . $file);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);
        $swf = curl_exec($curl);

        if (!is_string($swf) || $swf === '') {
            return Option\none();
        }

        if (!is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, recursive: true);
        }

        file_put_contents($cached, $swf);

        return Option\some($swf);
    }
}
