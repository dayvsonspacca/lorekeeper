<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native\Releases;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;

final readonly class ReleaseFeed
{
    public function __construct(
        private string $url,
    ) {}

    /** @throws HttpClientException */
    public function latest(): ?Release
    {
        $releases = Http::timeout(5)
            ->acceptJson()
            ->withUserAgent('Lorekeeper')
            ->get($this->url, ['per_page' => 30])
            ->throw()
            ->json();

        $latest = null;
        foreach (is_array($releases) ? $releases : [] as $release) {
            $found = Release::fromGitHub($release);
            if ($found !== null && ($latest === null || $found->newerThan($latest->version))) {
                $latest = $found;
            }
        }

        return $latest;
    }
}
