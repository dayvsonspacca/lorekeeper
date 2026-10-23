<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit\Scene;

use Lorekeeper\Apps\Desktop\Bots\Scene\StatSheet;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\Boosts;
use Lorekeeper\Core\Game\Attribute\Stat\Boost\MagicalBoost;
use Lorekeeper\Core\Game\Attribute\Stat\Haste;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\PrimaryAttributes;
use Lorekeeper\Core\Game\Attribute\Stat\Primary\Strength;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\DamageResistance;
use Lorekeeper\Core\Game\Attribute\Stat\Resistance\Resistances;
use Lorekeeper\Core\Game\Attribute\Stat\Stats;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StatSheetTest extends TestCase
{
    #[Test]
    public function it_lists_every_stat_by_group_as_the_stats_panel_shows_it(): void
    {
        $rows = StatSheet::of(new Stats(
            primary: new PrimaryAttributes(strength: new Strength(1245)),
            haste: new Haste(0.219),
            resistances: new Resistances(damage: new DamageResistance(1.2)),
            boosts: new Boosts(magical: new MagicalBoost(-0.4389)),
        ));
        $shown = array_column($rows, 'shown', 'name');

        static::assertCount(23, $rows);
        static::assertSame(
            ['Attributes', 'Combat', 'Modifiers'],
            array_values(array_unique(array_column($rows, 'group'))),
        );
        static::assertSame('1,245', $shown['Strength']);
        static::assertSame('21.9%', $shown['Haste']);
        static::assertSame('90%', $shown['Hit Chance']);
        static::assertSame('150%', $shown['Critical Multiplier']);
        static::assertSame('80% (capped)', $shown['Damage Resistance']);
        static::assertSame('-43.9%', $shown['Magical Boost']);
        static::assertArrayHasKey('DoT Resistance', $shown);
        static::assertSame(
            ['Attack Power', 'Spell Power', 'Hit Chance', 'Haste', 'Critical Chance', 'Critical Multiplier', 'Evasion'],
            array_column(
                array_values(array_filter($rows, static fn(array $row): bool => $row['group'] === 'Combat')),
                'name',
            ),
        );
        static::assertStringContainsString(
            'hard capped at 50%',
            $rows[array_search('Haste', array_column($rows, 'name'), strict: true)]['about'],
        );
    }
}
