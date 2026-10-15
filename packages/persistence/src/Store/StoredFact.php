<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Store;

use DateTimeImmutable;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Observation\Source;

final readonly class StoredFact
{
    /**
     * @param Fact<Subject, object> $fact
     * @param positive-int $timesSeen
     */
    public function __construct(
        public Fact $fact,
        public Source $source,
        public DateTimeImmutable $firstSeen,
        public DateTimeImmutable $lastSeen,
        public int $timesSeen,
    ) {}
}
