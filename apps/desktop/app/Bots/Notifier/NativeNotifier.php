<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Notifier;

use Illuminate\Http\Client\ConnectionException;
use Native\Desktop\Notification;
use Override;

final readonly class NativeNotifier implements Notifier
{
    #[Override]
    public function show(TaskNotice $notice): void
    {
        try {
            Notification::new()
                ->title($notice->title)
                ->message($notice->message)
                ->show();
        } catch (ConnectionException) {
            return;
        }
    }
}
