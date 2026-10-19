# Lorekeeper Client

A client for AdventureQuest Worlds. It logs in, enters the game, keeps your character up to date with what the server
says and runs scripts that play for you.

## What the client can do

- **Log in** through the game's web API and **pick a server**: the one you name, members only or not, as long as it
  is online; or, when you name none, the first online server open to every account.
- **Enter the game** and hand back your character whole: the map you are in and the monsters in it, your level,
  health and mana, the class you play with its points, rank and skills, and your inventory.
- **Keep the character up to date** while you play:
  - health and mana as fights go, dying and coming back to life;
  - level ups, class changes, the class points each kill gives and the skills you unlock;
  - the map you move to and the spot you arrive at in it, and whether you are away;
  - your inventory when it is sent again.
- **Send commands** to the server: join a map or your house, walk to a cell, attack a monster, come back to life,
  load your inventory, a shop or a quest, and log out.
- **Run scripts**, flows that send commands and go on only when the server answers:
  - every wait has a deadline, and a script says what never arrived;
  - a script can pause, report how far along it is and be stopped at any time;
  - a dropped connection ends a script instead of breaking it.
- **Tell you about every event** it hears, to log or show them.
- **Read the travel map**: the regions of the world and their maps.

## Playing

```php
$entered = Login::enter(new Credentials(new PlayerName('Hero'), getenv('AQW_PASSWORD')));
if ($entered instanceof Ending) {
    exit("Could not enter: {$entered->reason}" . PHP_EOL);
}

$entered->subscribe(static fn(Event $event) => print $event->summary() . PHP_EOL);

$me = $entered->character();
echo "{$me->player()->name} is level {$me->player()->level} in {$me->location()->map->name}" . PHP_EOL;

$outcome = Outcome::of($entered->run($script));
Login::leave($entered);
```

## Writing a script

A script yields what to do and gets back what the server answered:

```php
final readonly class GoToForest implements Script
{
    #[Override]
    public function title(): string
    {
        return 'Go to forest';
    }

    #[Override]
    public function run(Character $me): Generator
    {
        $joined = yield new Send(new JoinMapCommand($me->player()->name, new MapName('forest'), Option\none()))
            ->awaits(Duration::seconds(15), MapJoins::class, MapRefusals::class);

        if (MapRefusals::contains($joined)) {
            throw new RuntimeException("Could not join forest: {$joined->summary()}");
        }

        return new Done('Now in forest');
    }
}
```

For bots that play on their own, see [`bot`](../bot).
