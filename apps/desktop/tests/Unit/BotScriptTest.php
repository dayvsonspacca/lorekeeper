<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Tests\Unit;

use Lorekeeper\Apps\Desktop\Bots\BotScript;
use Lorekeeper\Apps\Desktop\Bots\ScriptView;
use Lorekeeper\Bot\Scripts\CryptoTokenScript;
use Lorekeeper\Bot\Scripts\LevelUpScript;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BotScriptTest extends TestCase
{
    #[Test]
    public function level_up_becomes_the_client_script_with_a_level_the_game_has(): void
    {
        static::assertInstanceOf(LevelUpScript::class, BotScript::LevelUp->script(['level' => '40']));
        static::assertSame('Level up to 40', BotScript::LevelUp->script(['level' => '40'])->title());
        static::assertSame('Level up to 100', BotScript::LevelUp->script(['level' => 250])->title());
        static::assertSame('Level up to 100', BotScript::LevelUp->script([])->title());
    }

    #[Test]
    public function level_up_summarizes_its_target_and_has_its_own_views(): void
    {
        static::assertSame('to level 40', BotScript::LevelUp->summary(['level' => '40']));
        static::assertSame('to level 100', BotScript::LevelUp->summary([]));
        static::assertSame('scripts.level-up.report', BotScript::LevelUp->view(ScriptView::Report));
        static::assertSame('scripts.level-up.plate', BotScript::LevelUp->view(ScriptView::Plate));
        static::assertSame(['level' => null], BotScript::blankParameters());
    }

    #[Test]
    public function the_crypto_token_daily_takes_nothing_and_has_its_own_views(): void
    {
        static::assertInstanceOf(CryptoTokenScript::class, BotScript::CryptoToken->script([]));
        static::assertSame([], BotScript::CryptoToken->defaults());
        static::assertSame([], BotScript::CryptoToken->fields());
        static::assertSame('for today', BotScript::CryptoToken->summary([]));
        static::assertSame('scripts.crypto-token.plate', BotScript::CryptoToken->view(ScriptView::Plate));
    }
}
