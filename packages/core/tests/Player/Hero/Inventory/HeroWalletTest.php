<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Player\Hero\Inventory;

use InvalidArgumentException;
use Lorekeeper\Core\Item\ItemPrice;
use Lorekeeper\Core\Player\Hero\Inventory\HeroWallet;
use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class HeroWalletTest extends TestCase
{
    #[Test]
    public function it_starts_empty(): void
    {
        $wallet = new HeroWallet();

        static::assertSame([0, 0], [$wallet->gold->value, $wallet->coins->value]);
    }

    #[Test]
    public function it_earns_and_pays_in_each_currency(): void
    {
        $wallet = new HeroWallet(new Gold(1000), new AdventureCoins(50));

        $wallet->earn(new Gold(500));
        $wallet->earn(new AdventureCoins(25));
        $wallet->pay(new Gold(300));
        $wallet->pay(new AdventureCoins(75));

        static::assertSame([1200, 0], [$wallet->gold->value, $wallet->coins->value]);
    }

    #[Test]
    public function it_rejects_paying_more_than_it_holds(): void
    {
        $wallet = new HeroWallet(new Gold(1000));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot pay 3000G holding only 1000G.');

        $wallet->pay(new Gold(3000));
    }

    #[Test]
    public function it_affords_only_what_it_can_pay(): void
    {
        $wallet = new HeroWallet(new Gold(1000), new AdventureCoins(50));

        static::assertTrue($wallet->affords(new ItemPrice(new Gold(1000))));
        static::assertFalse($wallet->affords(new ItemPrice(new Gold(1001))));
        static::assertFalse($wallet->affords(new ItemPrice(new AdventureCoins(51))));
    }

    #[Test]
    public function it_takes_the_totals_the_server_recounts(): void
    {
        $wallet = new HeroWallet(new Gold(1000), new AdventureCoins(50));

        $wallet->recount(new Gold(6_800_132), new AdventureCoins(2899));

        static::assertSame([6_800_132, 2899], [$wallet->gold->value, $wallet->coins->value]);
    }
}
