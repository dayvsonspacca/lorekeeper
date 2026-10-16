<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Feature;

use Lorekeeper\Apps\Wiki\Models\Account;
use Lorekeeper\Apps\Wiki\Models\BotRun;
use Lorekeeper\Apps\Wiki\Models\User;

trait CreatesBots
{
    private function botRun(): BotRun
    {
        $this->actingAs($this->user());

        return $this->account()->runs()->create();
    }

    private function account(): Account
    {
        return Account::query()->create(['name' => 'Hero', 'password' => bin2hex(random_bytes(8))]);
    }

    private function user(): User
    {
        return User::query()->firstOrCreate(['email' => 'hero@example.com'], [
            'name' => 'Hero',
            'password' => bin2hex(random_bytes(8)),
        ]);
    }
}
