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
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Quest\QuestId;
use Override;
use Psl\DateTime\Duration;

final class TokensOfCollectionScript extends BotScript
{
    use Joins;
    use Hunts;
    use Quests;
    use Loots;

    private const int QUEST = 1316;

    private const int CRICKET_DROP = 9392;

    private const int NEEDED = 2;

    private const int REWARD = 9397;

    private const int HUNTING_MINUTES = 10;

    #[Override]
    public function title(): string
    {
        return 'Tokens of Collection daily';
    }

    #[Override]
    protected function rules(): Generator
    {
        $quest = new QuestId(self::QUEST);
        $reward = new ItemId(self::REWARD);

        yield from $this->join(new MapName('terrarium'));
        $checking = $this->alreadyDone($quest);
        yield from $checking;
        if ($checking->getReturn()) {
            return new Done('Tokens of Collection was already taken today');
        }

        $accepting = $this->accept($quest);
        yield from $accepting;
        if (!$accepting->getReturn()) {
            return new Failed('the game would not give the quest Tokens of Collection');
        }

        $crickets = new MapSpot(new MapCell('r2'), MapPad::Left);
        $hunting = $this->hunt(
            $crickets,
            $this->attackIn($crickets->cell, new MonsterName('Carnivorous Cricket')),
            new ItemId(self::CRICKET_DROP),
            new Quantity(self::NEEDED),
            Duration::minutes(self::HUNTING_MINUTES),
        );
        yield from $hunting;
        if (!$hunting->getReturn()) {
            return new Failed(
                'fewer than '
                . self::NEEDED
                . ' tokens dropped after '
                . self::HUNTING_MINUTES
                . ' minutes of hunting crickets',
            );
        }

        $heldBefore = $this
            ->me()
            ->player()
            ->inventory->quantity($reward);
        $turningIn = $this->turnIn($quest);
        yield from $turningIn;
        $refusal = $turningIn->getReturn();
        if ($refusal !== null) {
            return new Failed("the game would not complete Tokens of Collection ({$refusal})");
        }

        $collecting = $this->collect($reward, $heldBefore);
        yield from $collecting;
        if (!$collecting->getReturn()) {
            return new Failed('Tokens of Collection was completed, but the Token of Collection could not be picked up');
        }

        $held = $this
            ->me()
            ->player()
            ->inventory->quantity($reward);

        return new Done("got today's Token of Collection, {$held} held now");
    }
}
