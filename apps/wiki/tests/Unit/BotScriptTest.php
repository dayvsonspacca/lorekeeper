<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Tests\Unit;

use InvalidArgumentException;
use Lorekeeper\Apps\Wiki\Bots\BotScript;
use Lorekeeper\Client\Bot\Task\AskQuestsTask;
use Lorekeeper\Client\Bot\Task\GoToMapTask;
use Lorekeeper\Client\Bot\Task\OpenShopsTask;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BotScriptTest extends TestCase
{
    #[Test]
    public function each_script_becomes_a_client_task(): void
    {
        static::assertEquals(new GoToMapTask(new MapName('forest')), BotScript::GoToMap->task(['map' => 'forest']));
        static::assertEquals(new AskQuestsTask(new MapName('battleon'), [
            new QuestId(3),
            new QuestId(5),
        ]), BotScript::AskQuests->task(['map' => '', 'quests' => ['3', '5']]));
        static::assertEquals(new OpenShopsTask(new MapName('battleon'), [new ShopId(
            31,
        )]), BotScript::OpenShops->task(['map' => 'battleon', 'shops' => [31, 'x']]));
    }

    #[Test]
    public function each_script_summarizes_its_parameters(): void
    {
        static::assertSame('forest', BotScript::GoToMap->summary(['map' => 'forest']));
        static::assertSame('battleon · 2 quests', BotScript::AskQuests->summary([
            'map' => 'battleon',
            'quests' => ['3', '5'],
        ]));
        static::assertSame('— · 0 shops', BotScript::OpenShops->summary([]));
        static::assertSame(['map' => null, 'quests' => null, 'shops' => null], BotScript::blankParameters());
    }

    #[Test]
    public function a_task_without_ids_cannot_be_played(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BotScript::OpenShops->task(['map' => 'battleon', 'shops' => ['x']]);
    }
}
