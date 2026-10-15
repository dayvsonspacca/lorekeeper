<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

use Psl\Option;
use Psl\Type;

final readonly class QuestDescription
{
    /** @var non-empty-string */
    public string $description;

    /** @var Option\Option<non-empty-string> */
    public Option\Option $endText;

    public function __construct(string $description, string $endText = '')
    {
        $this->description = Type\non_empty_string()->assert($description);
        $this->endText = '' === $endText ? Option\none() : Option\some($endText);
    }
}
