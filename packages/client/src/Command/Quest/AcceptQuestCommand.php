<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Quest;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class AcceptQuestCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private QuestId $quest,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%acceptQuest%{$this->area}%{$this->quest->value}%";
    }
}
