<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Script\Game;

use Generator;
use Lorekeeper\Client\Command\LoadQuestCommand;
use Lorekeeper\Client\Script\Await;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Client\Script\Send;
use Lorekeeper\Client\Script\TimedOut;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Server\AreaId;
use Lorekeeper\Server\Event\Quest\QuestLoadedEvent;
use Override;
use Psl\DateTime\Duration;

/** @implements Script<list<QuestLoadedEvent|TimedOut>> */
final readonly class LoadQuestsScript implements Script
{
    private const int BATCH = 10;

    /** @param non-empty-list<QuestId> $quests */
    public function __construct(
        private AreaId $area,
        private array $quests,
        private Duration $interval,
    ) {}

    #[Override]
    public function run(): Generator
    {
        $answers = [];

        foreach (array_chunk($this->quests, self::BATCH) as $index => $batch) {
            if ($index > 0) {
                yield new Pause($this->interval);
            }

            /** @var QuestLoadedEvent|TimedOut $answer */
            $answer = yield new Send(
                new LoadQuestCommand($this->area, $batch[0], ...array_slice($batch, offset: 1)),
                new Await([QuestLoadedEvent::class], Duration::seconds(15)),
            );
            $answers[] = $answer;
        }

        return $answers;
    }
}
