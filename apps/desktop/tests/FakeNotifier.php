<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests;

use Lorekeeper\Apps\Desktop\Bots\Notifier\Notice;
use Lorekeeper\Apps\Desktop\Bots\Notifier\Notifier;
use Override;

final class FakeNotifier implements Notifier
{
    /** @var list<array{string, string}> */
    public array $shown = [];

    #[Override]
    public function show(Notice $notice): void
    {
        $this->shown[] = [$notice->title, $notice->message];
    }
}
