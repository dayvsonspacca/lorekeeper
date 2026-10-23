<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Tests\Game\Tag;

use Lorekeeper\Core\Game\Tag\Tags;
use Lorekeeper\Core\Item\ItemTag;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TagsTest extends TestCase
{
    #[Test]
    public function it_has_only_the_tags_added_to_it(): void
    {
        /** @var Tags<ItemTag> $tags */
        $tags = new Tags();

        static::assertFalse($tags->has(ItemTag::MemberOnly));
        static::assertTrue($tags->add(ItemTag::MemberOnly)->add(ItemTag::MemberOnly)->has(ItemTag::MemberOnly));
    }
}
