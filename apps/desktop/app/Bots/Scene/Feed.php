<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots\Scene;

final readonly class Feed
{
    private const int LINES = 14;

    /** @param list<array{at: int, kind: string, text: string, detail: ?string}> $lines */
    public function __construct(
        public array $lines = [],
    ) {}

    public function add(int $at, FeedKind $kind, string $text, ?string $detail = null): self
    {
        return new self(array_slice(
            [['at' => $at, 'kind' => $kind->value, 'text' => $text, 'detail' => $detail], ...$this->lines],
            offset: 0,
            length: self::LINES,
        ));
    }
}
