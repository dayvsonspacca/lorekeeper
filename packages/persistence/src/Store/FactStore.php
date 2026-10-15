<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Store;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Observation\Observation;

interface FactStore
{
    /** @param list<Observation> $observations */
    public function record(array $observations): void;

    /** @return list<StoredFact> */
    public function about(Subject $subject): array;

    /**
     * @param class-string $valueClass
     *
     * @return list<StoredFact>
     */
    public function withValue(string $valueClass): array;
}
