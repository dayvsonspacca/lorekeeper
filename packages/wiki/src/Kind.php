<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki;

use Lorekeeper\Core\Knowledge\Subject;

final readonly class Kind
{
    /**
     * @param non-empty-string $key
     * @param class-string<Subject> $subjectClass
     * @param null|class-string $nameClass
     */
    public function __construct(
        public string $key,
        public string $singular,
        public string $plural,
        public string $subjectClass,
        public ?string $nameClass,
    ) {}
}
