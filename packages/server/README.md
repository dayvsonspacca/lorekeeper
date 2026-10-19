# Lorekeeper Server

Reads what the AdventureQuest Worlds game server says. Give it a message the server sent and it tells you what
happened, as typed events made of the game's own concepts from [`core`](../core). It holds no connection itself:
whoever talks to the server hands it the messages.

## What it reads

- **Logging in**: the connection being accepted, and the login succeeding, with the socket the server gives you, or
  failing.
- **Arriving in a map**: the area and room, the map with every monster spawned in it, each with its kind, cell,
  health, mana and whether it is idle, fighting or dead, and every player in the room with their level, health,
  mana, spot and whether they are away.
- **Being refused a map**: locked, members only, needing a quest or not available, or being already there.
- **The players in the room**: someone entering or leaving, their health and mana, and going away and coming back.
- **Fighting**: monsters losing health and dying, coming back, and what each kill gives in gold, experience and
  class points; players losing health and mana, dying and coming back to life; an attack refused or sent too soon.
- **Your class**: a class change, with its category, points and description, and the skills you are given, with
  their cost, cooldown, how many targets they hit and whether you have unlocked them.
- **Leveling up**, with how much experience the next level takes.
- **Your inventory**: every item as its kind, a weapon, a class, an armor, a house item, a resource and so on, with how
  many you have and whether it is for members only.
- **Equipment**: items equipped and unequipped, worn and taken off.
- **Shops**: shops that sell for gold or adventure coins and merge shops, with what each item costs.
- **Quests**: what a quest asks you to have before accepting it, what it asks you to bring and what it gives.

When a message does not read as expected, or holds a value it does not know, like a new kind of item, it says so with
a `MalformedEventException` instead of guessing.

## Example

```php
$message = JsonMessage::from($raw)->unwrap();

foreach (Events::recognize($message) as $event) {
    if ($event instanceof MalformedEventException) {
        continue;
    }

    if ($event instanceof MovedToMapEvent) {
        echo "{$event->map->name} has " . count($event->map->monsters) . ' monsters' . PHP_EOL;
    }

    echo $event->summary() . PHP_EOL;
}
```

One message can hold several events; an event that cannot read its part comes back as the exception and the others
are still read.
