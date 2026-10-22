<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Notifier;

use Override;

final readonly class SilentNotifier implements Notifier
{
    #[Override]
    public function show(Notice $notice): void {}
}
