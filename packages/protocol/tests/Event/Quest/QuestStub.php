<?php

declare(strict_types=1);

namespace Lorekeeper\Protocol\Tests\Event\Quest;

final readonly class QuestStub
{
    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    public static function quest(array $overrides = []): array
    {
        return [
            ...[
                'QuestID' => 3,
                'sName' => 'Chipped Tooth',
                'sDesc' => 'Bring back 10 Chipped Teeth.',
                'sEndText' => '',
                'iLvl' => 1,
                'iExp' => 0,
                'iGold' => 0,
                'iClass' => 0,
                'iReqCP' => 0,
                'FactionID' => 1,
                'sFaction' => 'None',
                'iReqRep' => 0,
                'iRep' => 0,
                'bUpg' => false,
            ],
            ...$overrides,
        ];
    }
}
