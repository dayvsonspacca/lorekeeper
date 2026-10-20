<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Desktop\Bots;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Lorekeeper\Bot\Scripts\CryptoTokenScript;
use Lorekeeper\Bot\Scripts\EldersBloodScript;
use Lorekeeper\Bot\Scripts\EnhanceScript;
use Lorekeeper\Bot\Scripts\LevelUpScript;
use Lorekeeper\Bot\Scripts\TokensOfCollectionScript;
use Lorekeeper\Client\Script\Script;
use Override;

enum BotScript: string implements HasLabel, HasDescription
{
    case LevelUp = 'level-up';
    case CryptoToken = 'crypto-token';
    case EldersBlood = 'elders-blood';
    case TokensOfCollection = 'tokens-of-collection';
    case Enhance = 'enhance';

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
            self::TokensOfCollection => 'Tokens of Collection daily',
            self::Enhance => 'Enhance the gear',
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
            self::TokensOfCollection
                => 'Tokens of Collection in terrarium: hunts two tokens from the Carnivorous Crickets, turns them in and picks up a Token of Collection, once a day.',
            self::Enhance
                => 'Buys and applies the strongest enhancement of the chosen type to every equipped weapon, class, helm and cape.',
        };
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return match ($this) {
            self::LevelUp => ['level' => 100],
            self::Enhance => ['type' => 'Lucky'],
            self::CryptoToken, self::EldersBlood, self::TokensOfCollection => [],
        };
    }

    /** @return list<Component|Field> */
    public function fields(): array
    {
        return match ($this) {
            self::LevelUp => [Parameters::levelField()],
            self::Enhance => [Parameters::enhancementTypeField()],
            self::CryptoToken, self::EldersBlood, self::TokensOfCollection => [],
        };
    }

    /** @param array<string, mixed> $parameters */
    public function summary(array $parameters): string
    {
        return match ($this) {
            self::LevelUp => 'to level ' . Parameters::level($parameters)->value,
            self::Enhance => 'with ' . Parameters::enhancementType($parameters)->name,
            self::CryptoToken, self::EldersBlood, self::TokensOfCollection => 'for today',
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
            self::TokensOfCollection => new TokensOfCollectionScript(),
            self::Enhance => new EnhanceScript(Parameters::enhancementType($parameters)),
        };
    }
}
