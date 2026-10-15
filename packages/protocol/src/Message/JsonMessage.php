<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Message;

use Override;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class JsonMessage implements Message
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public array $data,
        public string $raw,
    ) {}

    /** @return Option\Option<static> */
    #[Override]
    public static function from(string $message): Option\Option
    {
        $type = Type\json_decoded(Type\shape([
            'b' => Type\shape([
                'o' => Type\dict(Type\string(), Type\mixed()),
            ], allowUnknownFields: true),
        ], allowUnknownFields: true));

        try {
            $json = $type->coerce($message);
        } catch (CoercionException) {
            return Option\none();
        }

        return Option\some(new self($json['b']['o'], $message));
    }
}
