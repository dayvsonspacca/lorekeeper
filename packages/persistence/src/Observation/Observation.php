<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Observation;

use DateTimeImmutable;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;

final readonly class Observation
{
    /** @param Fact<Subject, object> $fact */
    public function __construct(
        public Fact $fact,
        public Source $source,
        public DateTimeImmutable $observedAt,
    ) {}
}
