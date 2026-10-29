<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Facts;

/**
 * @template-covariant TSubject of object
 * @template-covariant TValue of object
 */
final readonly class Fact
{
    /**
     * @param TSubject $subject
     * @param TValue $value
     */
    public function __construct(
        public object $subject,
        public object $value,
    ) {}
}
