<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Native\Releases;

final readonly class Release
{
    public const string TAG = 'desktop-v';

    public function __construct(
        public string $version,
        public string $url,
    ) {}

    public static function fromGitHub(mixed $release): ?self
    {
        if (!is_array($release) || ($release['draft'] ?? true) !== false) {
            return null;
        }

        $tag = $release['tag_name'] ?? null;
        if (!is_string($tag) || !str_starts_with($tag, self::TAG)) {
            return null;
        }

        return self::fromArray(['version' => substr($tag, strlen(self::TAG)), 'url' => $release['html_url'] ?? null]);
    }

    public static function fromArray(mixed $release): ?self
    {
        if (!is_array($release) || !is_string($release['version'] ?? null) || !is_string($release['url'] ?? null)) {
            return null;
        }

        return new self($release['version'], $release['url']);
    }

    public function newerThan(string $version): bool
    {
        return version_compare($this->version, $version, operator: '>');
    }

    /** @return array{version: string, url: string} */
    public function toArray(): array
    {
        return ['version' => $this->version, 'url' => $this->url];
    }
}
