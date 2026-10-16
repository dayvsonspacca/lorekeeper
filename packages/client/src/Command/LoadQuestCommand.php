<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Command;

use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use Override;

final readonly class LoadQuestCommand implements Command
{
    /** @var non-empty-list<QuestId> */
    private array $questIds;

    public function __construct(
        private AreaId $areaId,
        QuestId $questId,
        QuestId ...$more,
    ) {
        $this->questIds = [$questId, ...array_values($more)];
    }

    #[Override]
    public function toMessage(): string
    {
        $ids = implode('%', array_map(static fn(QuestId $id): string => (string) $id->value, $this->questIds));

        return "%xt%zm%getQuests%{$this->areaId}%{$ids}%";
    }
}
