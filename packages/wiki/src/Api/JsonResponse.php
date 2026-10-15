<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Api;

final readonly class JsonResponse
{
    public function __construct(
        public int $status,
        public mixed $data,
    ) {}

    public function body(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
