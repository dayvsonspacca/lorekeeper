<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Routine;

use Closure;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Miner\Exception\TravelMapUnavailableException;
use Lorekeeper\Miner\Recorder;
use Lorekeeper\Persistence\Observation\Source;

final readonly class TravelMapRoutine
{
    /**
     * @param Closure(): list<Fact<Subject, object>> $fetch
     * @param Closure(string): void $log
     */
    public function __construct(
        private Closure $fetch,
        private Recorder $recorder,
        private Closure $log,
    ) {}

    /**
     * @throws TravelMapUnavailableException
     *
     * @return int<0, max>
     */
    public function run(): int
    {
        ($this->log)('> fetch travel map');
        $facts = ($this->fetch)();

        $this->recorder->record($facts, Source::TravelMap);

        ($this->log)('< ' . count($facts) . ' facts recorded');

        return count($facts);
    }
}
