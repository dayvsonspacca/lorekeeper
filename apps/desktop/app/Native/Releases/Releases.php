<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native\Releases;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Cache;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notice;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Lorekeeper\Apps\Desktop\Models\Preferences;

final readonly class Releases
{
    private const string LATEST = 'lorekeeper.releases.latest';

    private const string ANNOUNCED = 'lorekeeper.releases.announced';

    private const int EVERY = 6 * 60 * 60;

    private const int RETRY = 10 * 60;

    public function __construct(
        private ?string $current,
        private ReleaseFeed $feed,
    ) {}

    public static function make(): self
    {
        $current = config('desktop.version');

        return new self(
            is_string($current) && $current !== '' ? $current : null,
            new ReleaseFeed((string) config('desktop.releases')),
        );
    }

    public function check(Notifier $notifier): void
    {
        if (!$this->wanted()) {
            return;
        }

        $release = $this->newer($this->latest());
        if ($release === null || Cache::get(self::ANNOUNCED) === $release->version) {
            return;
        }

        Cache::forever(self::ANNOUNCED, $release->version);
        $notifier->show(new Notice("Lorekeeper {$release->version} is out", 'Download it from the sidebar.'));
    }

    public function available(): ?Release
    {
        return $this->wanted() ? $this->newer(Release::fromArray(Cache::get(self::LATEST))) : null;
    }

    private function wanted(): bool
    {
        return $this->current !== null && Preferences::current()->notify_updates;
    }

    private function newer(?Release $release): ?Release
    {
        return $release !== null && $this->current !== null && $release->newerThan($this->current) ? $release : null;
    }

    private function latest(): ?Release
    {
        if (Cache::has(self::LATEST)) {
            return Release::fromArray(Cache::get(self::LATEST));
        }

        try {
            $release = $this->feed->latest();
        } catch (HttpClientException) {
            Cache::put(self::LATEST, [], self::RETRY);

            return null;
        }

        Cache::put(self::LATEST, $release?->toArray() ?? [], self::EVERY);

        return $release;
    }
}
