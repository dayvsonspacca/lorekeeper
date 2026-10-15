<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Api;

use Lorekeeper\Persistence\Store\FactStore;

final readonly class NewPages
{
    public function __construct(
        private FactStore $store,
        private Serializer $serializer,
    ) {}

    /** @return list<array{subject: array{kind: string, id: string, name: string}, added: string}> */
    public function list(): array
    {
        $pages = [];

        foreach ($this->store->latest(2000) as $stored) {
            $subject = $this->serializer->subject($stored->fact->subject);
            $key = "{$subject['kind']}/{$subject['id']}";
            if (array_key_exists($key, $pages) || str_starts_with($subject['name'], '#')) {
                continue;
            }

            $pages[$key] = ['subject' => $subject, 'added' => $stored->firstSeen->format(DATE_ATOM)];
            if (count($pages) === 12) {
                break;
            }
        }

        return array_values($pages);
    }
}
