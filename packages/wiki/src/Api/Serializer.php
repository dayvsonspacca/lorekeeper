<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Api;

use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Persistence\Codec\Codecs;
use Lorekeeper\Persistence\Store\StoredFact;
use Lorekeeper\Wiki\Names;
use Lorekeeper\Wiki\Targets;
use Throwable;

final readonly class Serializer
{
    public function __construct(
        private Codecs $codecs,
        private Names $names,
    ) {}

    /** @return array{kind: string, id: string, name: string} */
    public function subject(Subject $subject): array
    {
        $codec = $this->codecs->subjectFor($subject);

        return ['kind' => $codec->key, 'id' => $codec->encode($subject), 'name' => $this->names->of($subject)];
    }

    /** @return array<string, mixed> */
    public function factWithSubject(StoredFact $stored): array
    {
        return ['subject' => $this->subject($stored->fact->subject), ...$this->fact($stored)];
    }

    /** @return array<string, mixed> */
    public function fact(StoredFact $stored): array
    {
        $value = $stored->fact->value;
        $codec = $this->codecs->valueFor($value);
        $target = Targets::of($value);

        return [
            'type' => $codec->key(),
            'value' => $codec->encode($value),
            'ref' => $target === null ? null : $this->ref($target),
            'source' => $stored->source->value,
            'firstSeen' => $stored->firstSeen->format(DATE_ATOM),
            'lastSeen' => $stored->lastSeen->format(DATE_ATOM),
            'timesSeen' => $stored->timesSeen,
        ];
    }

    /** @return null|array{kind: string, id: string, name: string} */
    private function ref(Subject $subject): ?array
    {
        try {
            return $this->subject($subject);
        } catch (Throwable) {
            return null;
        }
    }
}
