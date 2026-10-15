<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Knowledge;

/**
 * @template-covariant TSubject of Subject
 * @template-covariant TValue of object
 */
final readonly class Fact
{
    /**
     * @param TSubject $subject
     * @param TValue $value
     */
    public function __construct(
        public Subject $subject,
        public object $value,
    ) {}
}
