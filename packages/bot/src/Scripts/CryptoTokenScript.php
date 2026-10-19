<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Scripts;

use Generator;
use Lorekeeper\Bot\Abilities\Moves\Hunts;
use Lorekeeper\Bot\Abilities\Moves\Joins;
use Lorekeeper\Bot\Abilities\Quests\Loots;
use Lorekeeper\Bot\Abilities\Quests\Quests;
use Lorekeeper\Bot\BotScript;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Failed;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapPad;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Core\World\Quest\QuestId;
use Override;
use Psl\DateTime\Duration;

final class CryptoTokenScript extends BotScript
{
    use Joins;
    use Hunts;
    use Quests;
    use Loots;

    private const int QUEST = 6187;

    private const int METAL_ORE = 42_658;

    private const int REWARD = 42_657;

    private const int HUNTING_MINUTES = 5;

    #[Override]
    public function title(): string
    {
        return 'Crypto Token daily';
    }

    /** @mago-expect analysis:redundant-condition */
    #[Override]
    protected function rules(): Generator
    {
        $quest = new QuestId(self::QUEST);
        $closet = new MapSpot(new MapCell('Closet'), MapPad::Center);
        $reward = new ItemId(self::REWARD);

        yield from $this->join(new MapName('boxes'));
        if (!yield from $this->accept($quest)) {
            return new Failed('the server did not accept the Crypto Token quest');
        }

        $found = yield from $this->hunt(
            $closet,
            $this->attackIn($closet->cell),
            new ItemId(self::METAL_ORE),
            new Quantity(1),
            Duration::minutes(self::HUNTING_MINUTES),
        );
        if (!$found) {
            return new Failed(
                'no Metal Ore after ' . self::HUNTING_MINUTES . ' minutes; the daily may be done for today',
            );
        }

        if (!yield from $this->turnIn($quest)) {
            return new Failed('the server did not take the Metal Ore');
        }

        if (!yield from $this->pickUp($reward)) {
            return new Failed('the quest was turned in but the Crypto Token was never picked up');
        }

        return new Done('Got a Crypto Token, now holding ' . $this
            ->me()
            ->player()
            ->inventory->quantity($reward));
    }
}
