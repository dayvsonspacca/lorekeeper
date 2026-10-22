<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Lorekeeper\Apps\Desktop\Native\Releases\ReleaseFeed;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ReleaseFeedTest extends TestCase
{
    private const string URL = 'https://api.github.com/repos/dayvsonspacca/lorekeeper/releases';

    #[Test]
    public function the_newest_published_desktop_release_wins(): void
    {
        Http::fake([
            self::URL . '*' => Http::response([
                self::release('desktop-v0.0.2-alpha'),
                self::release('desktop-v0.0.3-alpha', draft: true),
                self::release('v0.1.0'),
                self::release('desktop-v0.0.10-alpha'),
                self::release('desktop-v0.0.1-alpha'),
            ]),
        ]);

        $latest = new ReleaseFeed(self::URL)->latest();

        static::assertSame('0.0.10-alpha', $latest?->version);
        static::assertSame(
            'https://github.com/dayvsonspacca/lorekeeper/releases/tag/desktop-v0.0.10-alpha',
            $latest->url,
        );
        Http::assertSent(static fn($request): bool => $request->hasHeader('User-Agent', 'Lorekeeper'));
    }

    #[Test]
    public function anything_that_is_not_a_release_is_skipped(): void
    {
        Http::fake([
            self::URL . '*' => Http::response([
                'oops',
                ['draft' => false, 'tag_name' => 7],
                ['draft' => false, 'tag_name' => 'desktop-v0.0.2'],
            ]),
        ]);

        static::assertNull(new ReleaseFeed(self::URL)->latest());
    }

    #[Test]
    public function an_answer_that_is_not_a_list_has_no_release(): void
    {
        Http::fake([self::URL . '*' => Http::response('"none"')]);

        static::assertNull(new ReleaseFeed(self::URL)->latest());
    }

    #[Test]
    public function a_refused_request_is_a_failure(): void
    {
        Http::fake([self::URL . '*' => Http::response(['message' => 'rate limited'], 403)]);

        $this->expectException(RequestException::class);

        new ReleaseFeed(self::URL)->latest();
    }

    /** @return array{tag_name: string, draft: bool, html_url: string} */
    private static function release(string $tag, bool $draft = false): array
    {
        return [
            'tag_name' => $tag,
            'draft' => $draft,
            'html_url' => "https://github.com/dayvsonspacca/lorekeeper/releases/tag/{$tag}",
        ];
    }
}
