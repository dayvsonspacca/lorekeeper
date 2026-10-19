<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Lorekeeper\Bot\Scripts\CryptoTokenScript;
use Lorekeeper\Bot\Scripts\EldersBloodScript;
use Lorekeeper\Bot\Scripts\LevelUpScript;
use Lorekeeper\Client\Script\Script;
use Override;

enum BotScript: string implements HasLabel, HasDescription
{
    case LevelUp = 'level-up';
    case CryptoToken = 'crypto-token';
    case EldersBlood = 'elders-blood';

    /** @return array<string, null> */
    public static function blankParameters(): array
    {
        $keys = array_merge(...array_map(static fn(self $script): array => array_keys(
            $script->defaults(),
        ), self::cases()));

        return array_fill_keys($keys, value: null);
    }

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::LevelUp => 'Level up',
            self::CryptoToken => 'Crypto Token daily',
            self::EldersBlood => "Elders' Blood daily",
        };
    }

    #[Override]
    public function getDescription(): string
    {
        return match ($this) {
            self::LevelUp
                => 'Fights until the character reaches the level: oaklore, then swordhavenundead, then icestormarena.',
            self::CryptoToken
                => "Odd's 'n' Ends in boxes: hunts a Sneevil's Metal Ore, turns it in and picks up a Crypto Token, once a day.",
            self::EldersBlood
                => "Experiment 107 in arcangrove: slays 50 Gorillaphants, turns them in and picks up an Elders' Blood, once a day.",
        };
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return match ($this) {
            self::LevelUp => ['level' => 100],
            self::CryptoToken, self::EldersBlood => [],
        };
    }

    /** @return list<Component|Field> */
    public function fields(): array
    {
        return match ($this) {
            self::LevelUp => [Parameters::levelField()],
            self::CryptoToken, self::EldersBlood => [],
        };
    }

    /** @param array<string, mixed> $parameters */
    public function summary(array $parameters): string
    {
        return match ($this) {
            self::LevelUp => 'to level ' . Parameters::level($parameters)->value,
            self::CryptoToken, self::EldersBlood => 'for today',
        };
    }

    public function view(ScriptView $view): string
    {
        return "scripts.{$this->value}.{$view->value}";
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return Script<object>
     */
    public function script(array $parameters): Script
    {
        return match ($this) {
            self::LevelUp => new LevelUpScript(Parameters::level($parameters)),
            self::CryptoToken => new CryptoTokenScript(),
            self::EldersBlood => new EldersBloodScript(),
        };
    }
}
