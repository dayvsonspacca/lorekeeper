<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Closure;
use Lorekeeper\Core\Knowledge\Subject;
use Psl\Type;

final readonly class SubjectCodec
{
    /**
     * @param non-empty-string $key
     * @param class-string<Subject> $class
     * @param Closure(string): Subject $decode
     */
    public function __construct(
        public string $key,
        public string $class,
        private Closure $decode,
    ) {}

    /** @return non-empty-string */
    public function encode(Subject $subject): string
    {
        return Type\non_empty_string()->assert((string) $subject);
    }

    public function decode(string $id): Subject
    {
        return ($this->decode)($id);
    }
}
