# Lorekeeper Bot

Bots for AdventureQuest Worlds. A bot script runs on a [`client`](../client) already in the game and plays on its own
until its goal is reached.

## What a bot can do

- **Join a map**, and stop with the reason when the game refuses it: the map is locked, members only, needs a quest
  or is not available.
- **Walk to a spot**, a cell and the pad it comes in through.
- **Fight in a cell** until a condition you give is met:
  - it hits the monster you aim at and, once it dies, the other monsters of the same kind in that cell, never another
    kind;
  - it uses the unlocked skills that are ready and that it has the mana for, attacking skills before the auto attack;
  - it waits only until the next skill is ready;
  - when it dies, it waits, comes back to life and walks back to the same spot.
- **Report how far along it is**, so whoever runs it can show progress.

## Writing a bot

Extend `BotScript`, take the abilities your bot needs from `Abilities\Moves` (`Joins`, `Jumps`, `Kills`) and write
its `rules()`:

```php
final class FarmZardmen extends BotScript
{
    use Joins;
    use Kills;

    #[Override]
    public function title(): string
    {
        return 'Farm zardmen in forest';
    }

    #[Override]
    protected function rules(): Generator
    {
        yield from $this->join(new MapName('forest'));
        yield from $this->kill(
            new MapSpot(new MapCell('Forest2'), MapPad::Left),
            new AttackMonsterCommand($this->me()->location()->area, SkillSlot::AutoAttack, new MonsterSpawnId(4)),
            fn(): bool => $this->me()->player()->level->value >= 10,
        );

        return new Done('Level 10 in forest');
    }
}
```

## Credits

The logic of the bot scripts is based on the scripts of [Skua](https://github.com/auqw/Skua), found in
[auqw/Scripts](https://github.com/auqw/Scripts).
