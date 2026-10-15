<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Message;

use Override;
use Psl\Option;

final readonly class DelimitedMessage implements Message
{
    /** @param non-empty-list<string> $data */
    public function __construct(
        public array $data,
        public string $raw,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(string $message): Option\Option
    {
        if (!str_starts_with($message, '%') || !str_ends_with($message, '%')) {
            return Option\none();
        }

        $segments = array_values(array_filter(
            explode('%', $message),
            static fn(string $segment): bool => $segment !== '',
        ));

        if ($segments === []) {
            return Option\none();
        }

        return Option\some(new self($segments, $message));
    }
}
