<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorekeeper\Apps\Wiki\Models\User;
use Lorekeeper\Apps\Wiki\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_are_sent_to_the_login(): void
    {
        $this->get('/')->assertRedirect('/admin');
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    #[Test]
    public function users_land_on_the_accounts(): void
    {
        $hero = User::query()->create([
            'name' => 'Hero',
            'email' => 'hero@example.com',
            'password' => bin2hex(random_bytes(16)),
        ]);

        $this->actingAs($hero)->get('/admin')->assertRedirect('/admin/accounts');
        $this->actingAs($hero)->get('/admin/accounts')->assertOk();
    }
}
