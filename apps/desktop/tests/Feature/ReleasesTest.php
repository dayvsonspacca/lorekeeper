<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Lorekeeper\Apps\Desktop\Models\Preferences;
use Lorekeeper\Apps\Desktop\Native\Releases\Releases;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;

final class ReleasesTest extends TestCase
{
    use RefreshDatabase;

    private const string URL = 'https://api.github.com/repos/dayvsonspacca/lorekeeper/releases';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['desktop.version' => '0.0.1-alpha', 'desktop.releases' => self::URL]);
    }

    #[Test]
    public function a_newer_desktop_release_is_announced_once_and_offered_in_the_sidebar(): void
    {
        Http::fake([
            self::URL . '*' => Http::response([
                self::release('desktop-v0.0.2-alpha'),
                self::release('desktop-v0.0.3-alpha', draft: true),
                self::release('v0.1.0'),
                self::release('desktop-v0.0.1-alpha'),
            ]),
        ]);

        static::assertNull(Releases::make()->available());

        Releases::make()->check($this->notifier);
        Releases::make()->check($this->notifier);

        static::assertSame(
            [['Lorekeeper 0.0.2-alpha is out', 'Download it from the sidebar.']],
            $this->notifier->shown,
        );
        static::assertSame('0.0.2-alpha', Releases::make()->available()?->version);
        Http::assertSentCount(1);
        $this
            ->get('/')
            ->assertSee('Download Lorekeeper 0.0.2-alpha')
            ->assertSee('https://github.com/dayvsonspacca/lorekeeper/releases/tag/desktop-v0.0.2-alpha');
    }

    #[Test]
    public function the_version_already_running_is_not_announced(): void
    {
        Http::fake([self::URL . '*' => Http::response([self::release('desktop-v0.0.1-alpha')])]);

        Releases::make()->check($this->notifier);

        static::assertSame([], $this->notifier->shown);
        static::assertNull(Releases::make()->available());
        $this->get('/')->assertDontSee('Download Lorekeeper');
    }

    #[Test]
    public function a_prerelease_comes_before_its_final_version(): void
    {
        config(['desktop.version' => '0.0.2-alpha']);
        Http::fake([self::URL . '*' => Http::response([self::release('desktop-v0.0.2')])]);

        Releases::make()->check($this->notifier);

        static::assertSame('0.0.2', Releases::make()->available()?->version);
    }

    #[Test]
    public function turning_the_announcements_off_stops_asking_github(): void
    {
        Http::fake();
        Preferences::current()->update(['notify_updates' => false]);

        Releases::make()->check($this->notifier);

        Http::assertNothingSent();
        static::assertSame([], $this->notifier->shown);
        static::assertNull(Releases::make()->available());
    }

    #[Test]
    public function outside_the_shell_nothing_is_checked(): void
    {
        Http::fake();
        config(['desktop.version' => null]);

        Releases::make()->check($this->notifier);

        Http::assertNothingSent();
        static::assertNull(Releases::make()->available());
    }

    #[Test]
    public function an_offline_check_tries_again_soon(): void
    {
        $online = false;
        Http::fake(static function () use (&$online) {
            if (!$online) {
                throw new ConnectionException('offline');
            }

            return Http::response([self::release('desktop-v0.0.2-alpha')]);
        });

        Releases::make()->check($this->notifier);
        $online = true;
        $this->travel(5)->minutes();
        Releases::make()->check($this->notifier);

        static::assertSame([], $this->notifier->shown);

        $this->travel(6)->minutes();
        Releases::make()->check($this->notifier);

        static::assertSame(
            [['Lorekeeper 0.0.2-alpha is out', 'Download it from the sidebar.']],
            $this->notifier->shown,
        );
    }

    #[Test]
    public function a_new_version_is_looked_for_a_few_times_a_day(): void
    {
        Http::fake([
            self::URL . '*' => Http::sequence()
                ->push([self::release('desktop-v0.0.1-alpha')])
                ->push([self::release('desktop-v0.0.2-alpha')]),
        ]);

        Releases::make()->check($this->notifier);
        $this->travel(5)->hours();
        Releases::make()->check($this->notifier);

        static::assertSame([], $this->notifier->shown);

        $this->travel(2)->hours();
        Releases::make()->check($this->notifier);

        static::assertSame('0.0.2-alpha', Releases::make()->available()?->version);
        Http::assertSentCount(2);
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
