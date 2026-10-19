<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command\Quest;

use Lorekeeper\Client\Command\Command;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class TurnInQuestCommand implements Command
{
    public function __construct(
        private AreaId $area,
        private QuestId $quest,
        private ?ItemId $chosenReward = null,
        private Quantity $times = new Quantity(1),
    ) {}

    #[Override]
    public function toMessage(): string
    {
        $reward = $this->chosenReward->value ?? -1;

        return "%xt%zm%tryQuestComplete%{$this->area}%{$this->quest->value}%{$reward}%false%{$this->times->value}%wvz%";
    }
}
