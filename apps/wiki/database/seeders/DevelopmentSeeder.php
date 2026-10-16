<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Database\Seeders;

use Illuminate\Database\Seeder;
use Lorekeeper\Apps\Wiki\Models\User;

final class DevelopmentSeeder extends Seeder
{
    /** @mago-expect lint:no-literal-password */
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'hero@example.com'], ['name' => 'Hero', 'password' => 'password']);
    }
}
