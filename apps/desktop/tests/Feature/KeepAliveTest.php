<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Livewire\KeepAlive;
use Lorekeeper\Apps\Desktop\Native\Heartbeat;
use Lorekeeper\Apps\Desktop\Native\MainWindow;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
use Native\Desktop\Facades\Window;
use PHPUnit\Framework\Attributes\Test;

final class KeepAliveTest extends TestCase
{
    #[Test]
    public function a_window_that_never_beat_is_not_silent(): void
    {
        cache()->flush();

        static::assertFalse(Heartbeat::silent());
    }

    #[Test]
    public function the_window_goes_silent_when_the_page_stops_beating(): void
    {
        Livewire::test(KeepAlive::class);

        $this->travel(Heartbeat::PATIENCE_SECONDS)->seconds();
        static::assertFalse(Heartbeat::silent());

        $this->travel(1)->seconds();
        static::assertTrue(Heartbeat::silent());
    }

    #[Test]
    public function every_poll_keeps_the_window_alive(): void
    {
        $page = Livewire::test(KeepAlive::class);

        $this->travel(Heartbeat::PATIENCE_SECONDS + 1)->seconds();
        $page->call('beat');

        static::assertFalse(Heartbeat::silent());
    }

    #[Test]
    public function a_silent_window_is_reloaded_once(): void
    {
        $windows = Window::fake();
        Livewire::test(KeepAlive::class);

        MainWindow::reviveIfSilent();
        $windows->assertNotReloaded(MainWindow::ID);

        $this->travel(Heartbeat::PATIENCE_SECONDS + 1)->seconds();
        MainWindow::reviveIfSilent();
        MainWindow::reviveIfSilent();

        $windows->assertReloaded(MainWindow::ID);
        static::assertSame([MainWindow::ID], $windows->reloaded);
    }
}
