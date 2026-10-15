<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\Currency\Gold;
use Lorekeeper\Core\Item\DropRate;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Progression\Experience;
use Lorekeeper\Core\Progression\Rank;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\Reputation;
use Lorekeeper\Core\World\Quest\QuestDescription;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\Requirement\ClassRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\FactionRankRequirement;
use Lorekeeper\Core\World\Quest\Requirement\ItemRequirement;
use Lorekeeper\Core\World\Quest\Requirement\MemberRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PlayerLevelRequirement;
use Lorekeeper\Core\World\Quest\Requirement\PreviousQuestRequirement;
use Lorekeeper\Core\World\Quest\Reward\ExperienceReward;
use Lorekeeper\Core\World\Quest\Reward\GoldReward;
use Lorekeeper\Core\World\Quest\Reward\ItemReward;
use Lorekeeper\Core\World\Quest\Reward\ReputationReward;
use Lorekeeper\Core\World\Quest\TurnInItem;
use Psl\Type;

final readonly class QuestCodecs
{
    /** @return list<ValueCodec> */
    public static function all(): array
    {
        return [...self::basics(), ...self::requirements(), ...self::rewards()];
    }

    /** @return list<ValueCodec> */
    private static function basics(): array
    {
        return [
            new CustomCodec(
                'quest.description',
                QuestDescription::class,
                static function (object $v): array {
                    $d = Type\instance_of(QuestDescription::class)->assert($v);

                    return ['text' => $d->description, 'endText' => $d->endText->unwrapOr('')];
                },
                static function (mixed $p): QuestDescription {
                    $p = Type\shape(['text' => Type\string(), 'endText' => Type\string()])->assert($p);

                    return new QuestDescription($p['text'], $p['endText']);
                },
            ),
            new CustomCodec(
                'quest.turn_in',
                TurnInItem::class,
                static function (object $v): array {
                    $t = Type\instance_of(TurnInItem::class)->assert($v);

                    return ['item' => $t->itemId->value, 'quantity' => $t->quantity];
                },
                static function (mixed $p): TurnInItem {
                    $p = Type\shape(['item' => Type\positive_int(), 'quantity' => Type\int()])->assert($p);

                    return new TurnInItem(new ItemId($p['item']), $p['quantity']);
                },
            ),
        ];
    }

    /** @return list<ValueCodec> */
    private static function requirements(): array
    {
        return [
            new CustomCodec(
                'quest.requirement.player_level',
                PlayerLevelRequirement::class,
                static fn(object $v): int => Type\instance_of(PlayerLevelRequirement::class)->assert($v)->level->value,
                static fn(mixed $p): PlayerLevelRequirement => new PlayerLevelRequirement(new PlayerLevel(Type\int()->assert(
                    $p,
                ))),
            ),
            new CustomCodec(
                'quest.requirement.previous_quest',
                PreviousQuestRequirement::class,
                static fn(object $v): int => Type\instance_of(PreviousQuestRequirement::class)
                    ->assert($v)
                    ->questId
                    ->value,
                static fn(mixed $p): PreviousQuestRequirement => new PreviousQuestRequirement(new QuestId(Type\positive_int()->assert(
                    $p,
                ))),
            ),
            new CustomCodec(
                'quest.requirement.item',
                ItemRequirement::class,
                static fn(object $v): int => Type\instance_of(ItemRequirement::class)->assert($v)->itemId->value,
                static fn(mixed $p): ItemRequirement => new ItemRequirement(new ItemId(Type\positive_int()->assert(
                    $p,
                ))),
            ),
            new CustomCodec(
                'quest.requirement.member',
                MemberRequirement::class,
                static fn(object $v): bool => true,
                static fn(mixed $p): MemberRequirement => new MemberRequirement(),
            ),
            new CustomCodec(
                'quest.requirement.class_rank',
                ClassRankRequirement::class,
                static function (object $v): array {
                    $r = Type\instance_of(ClassRankRequirement::class)->assert($v);

                    return ['class' => $r->classId->value, 'rank' => $r->rank->value];
                },
                static function (mixed $p): ClassRankRequirement {
                    $p = Type\shape(['class' => Type\positive_int(), 'rank' => Type\int()])->assert($p);

                    return new ClassRankRequirement(new ClassId($p['class']), new Rank($p['rank']));
                },
            ),
            new CustomCodec(
                'quest.requirement.faction_rank',
                FactionRankRequirement::class,
                static function (object $v): array {
                    $r = Type\instance_of(FactionRankRequirement::class)->assert($v);

                    return ['faction' => $r->factionId->value, 'rank' => $r->rank->value];
                },
                static function (mixed $p): FactionRankRequirement {
                    $p = Type\shape(['faction' => Type\positive_int(), 'rank' => Type\int()])->assert($p);

                    return new FactionRankRequirement(new FactionId($p['faction']), new Rank($p['rank']));
                },
            ),
        ];
    }

    /** @return list<ValueCodec> */
    private static function rewards(): array
    {
        return [
            new CustomCodec(
                'quest.reward.gold',
                GoldReward::class,
                static fn(object $v): int => Type\instance_of(GoldReward::class)->assert($v)->gold->value,
                static fn(mixed $p): GoldReward => new GoldReward(new Gold(Type\int()->assert($p))),
            ),
            new CustomCodec(
                'quest.reward.experience',
                ExperienceReward::class,
                static fn(object $v): int => Type\instance_of(ExperienceReward::class)->assert($v)->experience->value,
                static fn(mixed $p): ExperienceReward => new ExperienceReward(new Experience(Type\int()->assert($p))),
            ),
            new CustomCodec(
                'quest.reward.reputation',
                ReputationReward::class,
                static function (object $v): array {
                    $r = Type\instance_of(ReputationReward::class)->assert($v);

                    return ['faction' => $r->factionId->value, 'amount' => $r->reputation->value];
                },
                static function (mixed $p): ReputationReward {
                    $p = Type\shape(['faction' => Type\positive_int(), 'amount' => Type\int()])->assert($p);

                    return new ReputationReward(new FactionId($p['faction']), new Reputation($p['amount']));
                },
            ),
            new CustomCodec(
                'quest.reward.item',
                ItemReward::class,
                static function (object $v): array {
                    $r = Type\instance_of(ItemReward::class)->assert($v);

                    return [
                        'item' => $r->itemId->value,
                        'dropRate' => $r->dropRate->percentage,
                        'quantity' => $r->quantity,
                    ];
                },
                static function (mixed $p): ItemReward {
                    $p = Type\shape([
                        'item' => Type\positive_int(),
                        'dropRate' => Type\num(),
                        'quantity' => Type\int(),
                    ])->assert($p);

                    return new ItemReward(new ItemId($p['item']), new DropRate((float) $p['dropRate']), $p['quantity']);
                },
            ),
        ];
    }
}
