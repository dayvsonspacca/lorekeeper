# Lorekeeper Wiki

A structured wiki for AdventureQuest Worlds. A script plays in the game while the wiki reads every message the server
sends, picks out facts about what it names (this item is called this, costs that, is sold in this shop) and keeps them
in tables made for each kind of thing.

```sh
just wiki up        # starts the wiki at http://192.168.77.10 with its database
AQW_PLAYER=Hero AQW_PASSWORD=secret just wiki explore scripts/BattleonShops.php --for=30
```
