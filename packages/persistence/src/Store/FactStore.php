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
     * @template T of Subject
     *
     * @param class-string<T> $subjectClass
     *
     * @return list<T>
     */
    public function subjects(string $subjectClass): array;

    /**
     * @param class-string<Subject> $subjectClass
     *
     * @return list<StoredFact>
     */
    public function aboutAll(string $subjectClass): array;

    /**
     * @param positive-int $limit
     *
     * @return list<StoredFact>
     */
    public function latest(int $limit): array;

    /** @return array<string, int> */
    public function countBySource(): array;

    /**
     * @param class-string $valueClass
     *
     * @return list<StoredFact>
     */
    public function withValue(string $valueClass): array;
}
