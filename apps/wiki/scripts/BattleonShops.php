<?php

declare(strict_types=1);

use Lorekeeper\Bot\Abilities\Moves\Joins;
use Lorekeeper\Bot\BotScript;
use Lorekeeper\Client\Command\Shop\LoadShopCommand;
use Lorekeeper\Client\Script\Done;
use Lorekeeper\Client\Script\Pause;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Shop\ShopId;
use Psl\DateTime\Duration;

return new class extends BotScript {
    use Joins;

    private const array SHOPS = [31];

    public function title(): string
    {
        return 'Battleon shops';
    }

    protected function rules(): Generator
    {
        yield from $this->join(new MapName('battleon'));
        $area = $this->me()->location()->area;
        foreach (self::SHOPS as $shop) {
            yield $this->send(new LoadShopCommand($area, new ShopId($shop)));
            yield new Pause(Duration::seconds(3));
        }

        return new Done(count(self::SHOPS) . ' shops opened');
    }
};
