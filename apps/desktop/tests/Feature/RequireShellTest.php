<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Desktop\Tauri\Poll;
use Lorekeeper\Apps\Desktop\Tauri\RequireShell;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Cookie;

final class RequireShellTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = '';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->secret = bin2hex(random_bytes(16));
        config(['desktop.shell' => 'tauri', 'desktop.shell_secret' => $this->secret]);
    }

    #[Test]
    public function the_window_enters_once_with_the_secret_and_keeps_it_in_a_cookie(): void
    {
        $entry = $this->get('/?' . RequireShell::QUERY . "={$this->secret}&tab=bots");

        $entry->assertRedirect();
        static::assertStringEndsWith('/?tab=bots', (string) $entry->headers->get('Location'));
        $cookie = $entry->getCookie(RequireShell::COOKIE, decrypt: false);
        static::assertNotNull($cookie);
        static::assertSame($this->secret, $cookie->getValue());
        static::assertTrue($cookie->isHttpOnly());
        static::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());

        $this->withUnencryptedCookie(RequireShell::COOKIE, $this->secret)->get('/')->assertOk();
    }

    #[Test]
    public function anyone_else_on_the_machine_is_turned_away(): void
    {
        $this->get('/')->assertForbidden();
        $this->get('/?' . RequireShell::QUERY . '=a-guess')->assertForbidden();
        $this->withUnencryptedCookie(RequireShell::COOKIE, 'a-guess')->get('/')->assertForbidden();
        $this->post('/livewire/update')->assertForbidden();
    }

    #[Test]
    public function the_shell_polls_with_its_header(): void
    {
        $this->getJson('/shell', [Poll::SECRET_HEADER => $this->secret])->assertOk();
    }

    #[Test]
    public function the_health_check_stays_open_for_the_shell_to_wait_on(): void
    {
        $this->get('/up')->assertOk();
    }

    #[Test]
    public function outside_the_shell_the_app_is_open_as_before(): void
    {
        config(['desktop.shell' => null]);

        $this->get('/')->assertOk();
    }
}
