<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Notifier;

interface Notifier
{
    public function show(Notice $notice): void;
}
