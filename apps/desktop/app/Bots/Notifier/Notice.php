<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Notifier;

use Lorekeeper\Apps\Desktop\Bots\BotStatus;
use Lorekeeper\Apps\Desktop\Models\Account;

final readonly class Notice
{
    public function __construct(
        public string $title,
        public string $message,
    ) {}

    public static function leaving(Account $account): ?self
    {
        return match ($account->status) {
            BotStatus::Disconnected => new self($account->name, 'Disconnected from the game'),
            BotStatus::Failed => new self($account->name, "Failed: {$account->result}"),
            default => null,
        };
    }
}
