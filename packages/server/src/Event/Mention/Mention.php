<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Mention;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\World\Faction\FactionId;

/**
 * @template-covariant TAbout of ItemId|FactionId
 * @template-covariant TValue of object
 */
final readonly class Mention
{
    /**
     * @param TAbout $about
     * @param TValue $value
     */
    public function __construct(
        public ItemId|FactionId $about,
        public object $value,
    ) {}
}
