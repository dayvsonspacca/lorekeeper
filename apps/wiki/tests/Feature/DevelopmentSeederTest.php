<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Lorekeeper\Apps\Wiki\Database\Seeders\DevelopmentSeeder;
use Lorekeeper\Apps\Wiki\Models\User;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class DevelopmentSeederTest extends TestCase
{
    use RefreshDatabase;

    /** @mago-expect lint:no-literal-password */
    #[Test]
    public function it_creates_the_hero_once_and_lets_them_log_in(): void
    {
        $this->seed(DevelopmentSeeder::class);
        $this->seed(DevelopmentSeeder::class);

        static::assertSame(1, User::query()->count());
        static::assertTrue(Auth::attempt(['email' => 'hero@example.com', 'password' => 'password']));
    }
}
