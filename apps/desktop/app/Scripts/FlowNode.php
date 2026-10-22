<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Scripts;

use Lorekeeper\Bot\Steps\Action;
use Lorekeeper\Bot\Steps\Check;

final readonly class FlowNode
{
    public const string NEXT = 'next';

    public const string FAILED = 'failed';

    public const string YES = 'yes';

    public const string NO = 'no';

    /**
     * @param non-empty-string $id
     * @param array{int, int} $at
     */
    public function __construct(
        public string $id,
        public Action|Check $step,
        public array $at = [0, 0],
    ) {}

    /** @return list<non-empty-string> */
    public function exits(): array
    {
        return $this->step instanceof Check ? [self::YES, self::NO] : [self::NEXT, self::FAILED];
    }
}
