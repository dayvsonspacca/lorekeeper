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

final class EldersBloodScript extends BotScript
{
    use Joins;
    use Hunts;
    use Quests;
    use Loots;

    private const int QUEST = 802;

    private const int SLAIN_GORILLAPHANT = 5574;

    private const int NEEDED = 50;

    private const int REWARD = 5586;

    private const int MOST_HELD = 20;

    private const int HUNTING_MINUTES = 20;

    #[Override]
    public function title(): string
    {
        return "Elders' Blood daily";
    }

    #[Override]
    protected function rules(): Generator
    {
        $quest = new QuestId(self::QUEST);
        $reward = new ItemId(self::REWARD);
        if ($this->me()->player()->inventory->quantity($reward) >= self::MOST_HELD) {
            return new Done('already holding the ' . self::MOST_HELD . " Elders' Blood the game allows");
        }

        yield from $this->join(new MapName('arcangrove'));
        $checking = $this->alreadyDone($quest);
        yield from $checking;
        if ($checking->getReturn()) {
            return new Done("Elders' Blood was already taken today");
        }

        $grove = new MapSpot(new MapCell('LeftBack'), MapPad::Left);
        $accepting = $this->accept($quest);
        yield from $accepting;
        if (!$accepting->getReturn()) {
            return new Failed("the game would not give the quest Experiment 107: Elder's Blood Potion");
        }

        $hunting = $this->hunt(
            $grove,
            $this->attackIn($grove->cell),
            new ItemId(self::SLAIN_GORILLAPHANT),
            new Quantity(self::NEEDED),
            Duration::minutes(self::HUNTING_MINUTES),
        );
        yield from $hunting;
        if (!$hunting->getReturn()) {
            return new Failed(
                'fewer than ' . self::NEEDED . ' Gorillaphants slain after ' . self::HUNTING_MINUTES . ' minutes',
            );
        }

        $turningIn = $this->turnIn($quest);
        yield from $turningIn;
        $refusal = $turningIn->getReturn();
        if ($refusal !== null) {
            return new Failed("the game would not complete Experiment 107 ({$refusal})");
        }

        $pickingUp = $this->pickUp($reward);
        yield from $pickingUp;
        if (!$pickingUp->getReturn()) {
            return new Failed("Experiment 107 was completed, but the Elders' Blood could not be picked up");
        }

        $held = $this
            ->me()
            ->player()
            ->inventory->quantity($reward);

        return new Done("got today's Elders' Blood, {$held} held now");
    }
}
