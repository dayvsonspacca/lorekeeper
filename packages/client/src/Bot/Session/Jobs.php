<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

interface Jobs
{
    public function next(): ?Job;

    public function cancelled(Job $job): bool;
}
