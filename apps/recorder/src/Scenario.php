<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Recorder;

use Lorekeeper\Bot\Scripts\LevelUpScript;
use Lorekeeper\Client\Script\Script;
use Lorekeeper\Core\Player\PlayerLevel;
use Psl\DateTime\Duration;

enum Scenario: string
{
    case Watch = 'watch';
    case LevelUp = 'level-up';

    public function description(): string
    {
        return match ($this) {
            self::Watch => 'Enter the game and stay where it lands, doing nothing',
            self::LevelUp => 'Play the level up script, up to --level (100 by default)',
        };
    }

    /**
     * @param array<string, string> $options
     *
     * @return Script<object>
     */
    public function script(array $options, Duration $length): Script
    {
        return match ($this) {
            self::Watch => new WatchScript($length),
            self::LevelUp => new LevelUpScript(new PlayerLevel(max(1, min(100, (int) ($options['level'] ?? 100))))),
        };
    }
}
