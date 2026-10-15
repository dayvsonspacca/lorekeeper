<?php

declare(strict_types=1);

namespace Lorekeeper\Miner;

use Closure;
use DateTimeImmutable;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Observation\Observation;
use Lorekeeper\Persistence\Observation\Source;
use Lorekeeper\Persistence\Store\FactStore;

final readonly class Recorder
{
    /** @param Closure(): DateTimeImmutable $clock */
    public function __construct(
        private FactStore $store,
        private Closure $clock,
    ) {}

    /** @param list<Fact<Subject, object>> $facts */
    public function record(array $facts, Source $source): void
    {
        $now = ($this->clock)();

        $this->store->record(array_map(
            static fn(Fact $fact): Observation => new Observation($fact, $source, $now),
            $facts,
        ));
    }
}
