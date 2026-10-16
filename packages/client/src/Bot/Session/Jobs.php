<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot\Session;

/**
 * Where a session gets its work from. The session asks for the next job whenever it is free, and keeps asking
 * whether the job it plays was cancelled.
 */
interface Jobs
{
    /** The job to play now, or null when there is nothing to do yet. */
    public function next(): ?Job;

    public function cancelled(Job $job): bool;
}
