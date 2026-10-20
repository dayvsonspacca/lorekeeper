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

    private static function refused(string $reason): string
    {
        return $reason === 'Missing Badge'
            ? "the game would not complete Odd's 'n' Ends: it was already done today"
            : "the game would not complete Odd's 'n' Ends ({$reason})";
    }

    #[Override]
    protected function rules(): Generator
    {
        $quest = new QuestId(self::QUEST);
        $closet = new MapSpot(new MapCell('Closet'), MapPad::Center);
        $reward = new ItemId(self::REWARD);

        yield from $this->join(new MapName('boxes'));
        $checking = $this->alreadyDone($quest);
        yield from $checking;
        if ($checking->getReturn()) {
            return new Done('Crypto Token was already taken today');
        }

        $accepting = $this->accept($quest);
        yield from $accepting;
        if (!$accepting->getReturn()) {
            return new Failed("the game would not give the quest Odd's 'n' Ends");
        }

        $hunting = $this->hunt(
            $closet,
            $this->attackIn($closet->cell),
            new ItemId(self::METAL_ORE),
            new Quantity(1),
            Duration::minutes(self::HUNTING_MINUTES),
        );
        yield from $hunting;
        if (!$hunting->getReturn()) {
            return new Failed('no Metal Ore dropped after ' . self::HUNTING_MINUTES . ' minutes of hunting Sneevils');
        }

        $heldBefore = $this
            ->me()
            ->player()
            ->inventory->quantity($reward);
        $turningIn = $this->turnIn($quest);
        yield from $turningIn;
        $refusal = $turningIn->getReturn();
        if ($refusal !== null) {
            return new Failed(self::refused($refusal));
        }

        $collecting = $this->collect($reward, $heldBefore);
        yield from $collecting;
        if (!$collecting->getReturn()) {
            return new Failed("Odd's 'n' Ends was completed, but the Crypto Token could not be picked up");
        }

        $held = $this
            ->me()
            ->player()
            ->inventory->quantity($reward);

        return new Done("got today's Crypto Token, {$held} held now");
    }
}
