<?php

declare(strict_types=1);

namespace Lorekeeper\Bot\Tests\Scripts;

use Lorekeeper\Bot\Scripts\CryptoTokenScript;
use Lorekeeper\Bot\Scripts\EldersBloodScript;
use Lorekeeper\Bot\Scripts\EnhanceScript;
use Lorekeeper\Bot\Scripts\LevelUpScript;
use Lorekeeper\Bot\Scripts\TokensOfCollectionScript;
use Lorekeeper\Core\Item\Misc\Enhancement\EnhancementType;
use Lorekeeper\Core\Player\PlayerLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ScriptTitlesTest extends TestCase
{
    #[Test]
    public function each_script_is_titled_after_what_it_plays(): void
    {
        static::assertSame('Crypto Token daily', new CryptoTokenScript()->title());
        static::assertSame("Elders' Blood daily", new EldersBloodScript()->title());
        static::assertSame('Tokens of Collection daily', new TokensOfCollectionScript()->title());
        static::assertSame('Enhance the equipped gear with Lucky', new EnhanceScript(EnhancementType::Lucky)->title());
        static::assertSame('Level up to 30', new LevelUpScript(new PlayerLevel(30))->title());
    }
}
