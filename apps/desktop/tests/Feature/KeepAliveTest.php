<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Feature;

use Livewire\Livewire;
use Lorekeeper\Apps\Desktop\Livewire\KeepAlive;
use Lorekeeper\Apps\Desktop\Native\Heartbeat;
use Lorekeeper\Apps\Desktop\Tests\TestCase;
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
    public function a_silent_window_is_revived_once(): void
    {
        Livewire::test(KeepAlive::class);
        static::assertFalse(Heartbeat::revive());

        $this->travel(Heartbeat::PATIENCE_SECONDS + 1)->seconds();

        static::assertTrue(Heartbeat::revive());
        static::assertFalse(Heartbeat::revive());
    }
}
