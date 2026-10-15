<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Api;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Store\FactStore;
use Lorekeeper\Persistence\Store\StoredFact;
use Lorekeeper\Wiki\Kind;
use Lorekeeper\Wiki\Kinds;
use Lorekeeper\Wiki\Names;
use Lorekeeper\Wiki\Targets;

final readonly class Queries
{
    public function __construct(
        private FactStore $store,
        private Serializer $serializer,
        private Names $names,
    ) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        $counts = [];
        foreach (Kinds::all() as $kind) {
            $counts[$kind->key] = count($this->store->subjects($kind->subjectClass));
        }

        return [
            'counts' => $counts,
            'sources' => $this->store->countBySource(),
            'newPages' => new NewPages($this->store, $this->serializer)->list(),
        ];
    }

    /** @return list<array{kind: string, id: string, name: string}> */
    public function searchIndex(): array
    {
        $index = [];
        foreach (Kinds::all() as $kind) {
            foreach ($this->store->subjects($kind->subjectClass) as $subject) {
                $index[] = $this->serializer->subject($subject);
            }
        }

        return $index;
    }

    /** @return array{kind: string, rows: list<array{id: string, name: string, facts: array<string, list<mixed>>}>} */
    public function listing(Kind $kind): array
    {
        $rows = [];
        foreach ($this->store->aboutAll($kind->subjectClass) as $stored) {
            $ref = $this->serializer->subject($stored->fact->subject);
            $fact = $this->serializer->fact($stored);
            $rows[$ref['id']] ??= ['id' => $ref['id'], 'name' => $ref['name'], 'facts' => []];
            $rows[$ref['id']]['facts'][(string) $fact['type']][] = $fact['value'];
        }

        return ['kind' => $kind->key, 'rows' => array_values($rows)];
    }

    /** @return array{subject: array{kind: string, id: string, name: string}, facts: list<array<string, mixed>>, references: list<array<string, mixed>>} */
    public function detail(Subject $subject, Kind $kind): array
    {
        return [
            'subject' => [...$this->serializer->subject($subject), 'kind' => $kind->key],
            'facts' => array_map(fn(StoredFact $f): array => $this->serializer->fact(
                $f,
            ), $this->store->about($subject)),
            'references' => $this->references($subject),
        ];
    }

    /** @return array{nodes: list<AtlasNode>, links: list<array{source: string, target: string}>} */
    public function atlas(): array
    {
        return new Atlas($this->store, $this->names)->build();
    }

    /** @return list<array<string, mixed>> */
    private function references(Subject $subject): array
    {
        $references = [];

        foreach (array_keys(Targets::PROPERTIES) as $valueClass) {
            foreach ($this->store->withValue($valueClass) as $stored) {
                $matches = array_filter(
                    Targets::all($stored->fact->value),
                    static fn(Subject $target): bool => (
                        $target::class === $subject::class
                        && (string) $target === (string) $subject
                    ),
                );
                if ($matches === []) {
                    continue;
                }

                $references[] = $this->serializer->factWithSubject($stored);
            }
        }

        return $references;
    }
}
