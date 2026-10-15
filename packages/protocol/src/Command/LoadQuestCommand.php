<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Command;

use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Protocol\AreaId;
use Override;

final readonly class LoadQuestCommand implements Command
{
    public function __construct(
        private AreaId $areaId,
        private QuestId $questId,
    ) {}

    #[Override]
    public function toMessage(): string
    {
        return "%xt%zm%getQuests%{$this->areaId}%{$this->questId}%";
    }
}
